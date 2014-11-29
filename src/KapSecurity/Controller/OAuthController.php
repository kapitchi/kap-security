<?php

namespace KapSecurity\Controller;

use KapSecurity\Authentication\Adapter\AdapterManager;
use KapSecurity\Authentication\Adapter\RedirectionAdapterInterface;
use KapSecurity\Authentication\AuthenticationService;
use OAuth2\Encryption\Jwt;
use OAuth2\Request as OAuth2Request;
use OAuth2\Response as OAuth2Response;
use OAuth2\Server as OAuth2Server;
use Zend\Http\PhpEnvironment\Request as PhpEnvironmentRequest;
use Zend\Http\Request as HttpRequest;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZF\ContentNegotiation\ViewModel;

class OAuthController extends \ZF\OAuth2\Controller\AuthController
{
    /**
     * @var OAuth2Server
     */
    protected $server;

    /**
     * @var AuthenticationService
     */
    protected $authenticationService;

    /**
     * @var \KapSecurity\Authentication\Adapter\AdapterManager
     */
    protected $adapterManager;

    public function __construct(OAuth2Server $server, AuthenticationService $authenticationService, AdapterManager $adapterManager)
    {
        $this->server = $server;
        $this->authenticationService = $authenticationService;
        $this->adapterManager = $adapterManager;
    }

    /**
     * Token Action (/oauth)
     */
    public function tokenAction()
    {
        $request = $this->getRequest();
        if (! $request instanceof HttpRequest) {
            // not an HTTP request; nothing left to do
            return;
        }

        if ($request->isOptions()) {
            // OPTIONS request.
            // This is most likely a CORS attempt; as such, pass the response on.
            return $this->getResponse();
        }

        $oauth2request = $this->getOAuth2Request();
        
        $response = $this->server->handleTokenRequest($oauth2request);
        if ($response->isClientError()) {
            $parameters = $response->getParameters();
            $errorUri   = isset($parameters['error_uri']) ? $parameters['error_uri'] : null;
            return new ApiProblemResponse(
                new ApiProblem(
                    $response->getStatusCode(),
                    $parameters['error_description'],
                    $errorUri,
                    $parameters['error']
                )
            );
        }
        return $this->setHttpResponse($response);
    }

    /**
     * Test resource (/oauth/resource)
     */
    public function resourceAction()
    {
        // Handle a request for an OAuth2.0 Access Token and send the response to the client
        if (!$this->server->verifyResourceRequest($this->getOAuth2Request())) {
            $response   = $this->server->getResponse();
            $parameters = $response->getParameters();
            $errorUri   = isset($parameters['error_uri']) ? $parameters['error_uri'] : null;
            return new ApiProblemResponse(
                new ApiProblem(
                    $response->getStatusCode(),
                    $parameters['error_description'],
                    $errorUri,
                    $parameters['error']
                )
            );
        }
        $httpResponse = $this->getResponse();
        $httpResponse->setStatusCode(200);
        $httpResponse->getHeaders()->addHeaders(array('Content-type' => 'application/json'));
        $httpResponse->setContent(
            json_encode(array('success' => true, 'message' => 'You accessed my APIs!'))
        );
        return $httpResponse;
    }

    /**
     * Authorize action (/oauth/authorize)
     */
    public function authorizeAction()
    {
        $request  = $this->getOAuth2Request();
        return $this->authorize($request);
    }
    
    public function callbackAction()
    {
        $authService = $this->getAuthenticationService();

        $state = $authService->decodeJwt($this->params()->fromQuery('state'));
        if(!$state) {
            throw new \InvalidArgumentException("Can't decode state from jwt token");
        }

        //TODO check params
        //if(empty($state['service']) || empty($state['requestParams']) || empty($state['service']))

        $service = $state['service'];
        $requestParams = $state['requestParams'];

        $adapter = $this->getAdapter($service);
        $result = $authService->authenticate($adapter);
        if(!$result->isValid()) {
            $response = new OAuth2Response();
            $redirectUri = $requestParams['redirect_uri'];
            if($redirectUri) {
                $response->setRedirect(302, $redirectUri, $requestParams['state'], 'authentication_failed', current($result->getMessages()), null);
            }
            else {
                $response->setError(400, 'authentication_failed', current($result->getMessages()));
            }

            return $this->handleResponse($response);
        }

        $request = $this->getOAuth2Request($state['requestParams']);
        return $this->authorize($request);
    }

    protected function getAdapter($name)
    {
        $adapterManager = $this->getAdapterManager();
        $adapter = $adapterManager->get($name);

        if(method_exists($adapter, 'setMvcEvent')) {
            $adapter->setMvcEvent($this->getEvent());
        }

        return $adapter;
    }

    protected function authorize(OAuth2Request $request)
    {
        $response = new OAuth2Response();

        $authService = $this->getAuthenticationService();

        // validate the authorize request
        if (!$this->server->validateAuthorizeRequest($request, $response)) {
            return $this->handleResponse($response);
        }

        if(!$authService->hasIdentity()) {
            return $this->handleNoIdentity();
        }

        $identityId = $authService->getIdentity();

        //TODO request authorization from an user 
        /**
        $authorized = $request->request('authorized', false);
        if (empty($authorized)) {
        $clientId = $request->query('client_id', false);
        $view = new ViewModel(array('clientId' => $clientId));
        $view->setTemplate('oauth/authorize');
        return $view;
        }
        $is_authorized = ($authorized === 'yes');
         */

        $is_authorized = true;

        $this->server->handleAuthorizeRequest($request, $response, $is_authorized, $identityId);

        return $this->handleResponse($response);
    }

    protected function handleNoIdentity()
    {
        $requestParams = $this->params()->fromQuery();
        $view = new ViewModel([
            'requestParams' => $requestParams,
        ]);
        
        $view->setTemplate('kap-security/login');
        return $view;
    }
    
    protected function handleResponse(OAuth2Response $response)
    {
        $redirect = $response->getHttpHeader('Location');
        if (!empty($redirect)) {
            return $this->redirect()->toUrl($redirect);
        }

        $parameters = $response->getParameters();
        $errorUri   = isset($parameters['error_uri']) ? $parameters['error_uri'] : null;

        $view = new ViewModel(array(
            'statusCode' => $response->getStatusCode(),
            'statusText' => $response->getStatusText(),
            'errorDescription' => $parameters['error_description'],
            'error' => $parameters['error'],
            'errorUri' => $errorUri
        ));

        $view->setTemplate('kap-security/oauth-authorize-error');
        return $view;
    }

    /**
     * Receive code action prints the code/token access
     */
    public function receiveCodeAction()
    {
        $code = $this->params()->fromQuery('code', false);
        $view = new ViewModel(array(
            'code' => $code
        ));
        $view->setTemplate('oauth/receive-code');
        return $view;
    }

    /**
     * Create an OAuth2 request based on the ZF2 request object
     *
     * Marshals:
     *
     * - query string
     * - body parameters, via content negotiation
     * - "server", specifically the request method and content type
     * - raw content
     * - headers
     *
     * This ensures that JSON requests providing credentials for OAuth2
     * verification/validation can be processed.
     *
     * @return OAuth2Request
     */
    protected function getOAuth2Request($params = null)
    {
        $zf2Request = $this->getRequest();
        $headers    = $zf2Request->getHeaders();

        // Marshal content type, so we can seed it into the $_SERVER array
        $contentType = '';
        if ($headers->has('Content-Type')) {
            $contentType = $headers->get('Content-Type')->getFieldValue();
        }

        // Get $_SERVER superglobal
        $server = array();
        if ($zf2Request instanceof PhpEnvironmentRequest) {
            $server = $zf2Request->getServer()->toArray();
        } elseif (!empty($_SERVER)) {
            $server = $_SERVER;
        }
        $server['REQUEST_METHOD'] = $zf2Request->getMethod();

        // Seed headers with HTTP auth information
        $headers = $headers->toArray();
        if (isset($server['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $server['PHP_AUTH_USER'];
        }
        if (isset($server['PHP_AUTH_PW'])) {
            $headers['PHP_AUTH_PW'] = $server['PHP_AUTH_PW'];
        }

        // Ensure the bodyParams are passed as an array
        $bodyParams = $this->bodyParams() ?: array();

        return new OAuth2Request(
            $params ? $params : $zf2Request->getQuery()->toArray(),
            $this->bodyParams(),
            array(), // attributes
            array(), // cookies
            array(), // files
            $server,
            $zf2Request->getContent(),
            $headers
        );
    }

    /**
     * Convert the OAuth2 response to a \Zend\Http\Response
     *
     * @param $response OAuth2Response
     * @return \Zend\Http\Response
     */
    private function setHttpResponse(OAuth2Response $response)
    {
        $httpResponse = $this->getResponse();
        $httpResponse->setStatusCode($response->getStatusCode());

        $headers = $httpResponse->getHeaders();
        $headers->addHeaders($response->getHttpHeaders());
        $headers->addHeaderLine('Content-type', 'application/json');

        $httpResponse->setContent($response->getResponseBody());
        return $httpResponse;
    }

    /**
     * @param \KapSecurity\Authentication\AuthenticationService $authenticationService
     */
    public function setAuthenticationService($authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    /**
     * @return \KapSecurity\Authentication\AuthenticationService
     */
    public function getAuthenticationService()
    {
        return $this->authenticationService;
    }

    /**
     * @param \KapSecurity\Authentication\Adapter\AdapterManager $adapterManager
     */
    public function setAdapterManager($adapterManager)
    {
        $this->adapterManager = $adapterManager;
    }

    /**
     * @return \KapSecurity\Authentication\Adapter\AdapterManager
     */
    public function getAdapterManager()
    {
        return $this->adapterManager;
    }
    
}
