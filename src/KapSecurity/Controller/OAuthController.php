<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace KapSecurity\Controller;

use KapSecurity\Authentication\Adapter\RedirectionAdapterInterface;
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
     * TODO this must go from config
     * @var string 
     */
    protected $jwtKey = '12345';

    /**
     * Constructor
     *
     * @param $server OAuth2Server
     */
    public function __construct(OAuth2Server $server)
    {
        $this->server = $server;
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
        $authService = $this->getServiceLocator()->get('KapSecurity\\Authentication\\AuthenticationService');

        $state = $this->decodeJwt($this->params()->fromQuery('state'));
        if(!$state) {
            echo __FILE__ . ' Line: ' . __LINE__; var_dump('state === false'); exit; //XXX
        }

        //authenticate if requested
        $service = $state['service'];
        $requestParams = $state['requestParams'];

        $adapterManager = $this->getServiceLocator()->get('KapSecurity/Authentication/Adapter/AdapterManager');
        $adapter = $adapterManager->get($service);

        if($adapter instanceof RedirectionAdapterInterface) {
            $view = $this->getServiceLocator()->get('ViewHelperManager');
            $serverHelper = $view->get('ServerUrl');
    
            $callbackUrl = $serverHelper($this->url()->fromRoute('kap-security.oauth-callback'));
            $adapter->setCallbackUrl($callbackUrl);
        }

        //TODO XXX
        if(method_exists($adapter, 'setMvcEvent')) {
            $adapter->setMvcEvent($this->getEvent());
        }

        $result = $authService->authenticate($adapter);
        if(!$result->isValid()) {

            echo __FILE__ . ' Line: ' . __LINE__; var_dump($result); exit; //XXX

            $redirectUri = $requestParams['redirect_uri'];
            if($redirectUri) {
                $response->setRedirect(302, $redirectUri, $requestParams['state'], 'authentication_failed', "User didn't authenticate", null);
            }
            else {
                $response->setError(400, 'authentication_failed', "User didn't authenticate");
            }

            return $this->handleResponse($response);
        }

        $request = $this->getOAuth2Request($state['requestParams']);
        return $this->authorize($request);
    }

    protected function authorize(OAuth2Request $request)
    {
        $response = new OAuth2Response();

        $authService = $this->getServiceLocator()->get('KapSecurity\\Authentication\\AuthenticationService');

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
        $view = $this->getServiceLocator()->get('ViewHelperManager');
        $serverHelper = $view->get('ServerUrl');

        $callbackUrl = $serverHelper($this->url()->fromRoute('kap-security.oauth-callback'));
        
        $adapterManager = $this->getServiceLocator()->get('KapSecurity/Authentication/Adapter/AdapterManager');
        
        //http://tools.ietf.org/html/draft-bradley-oauth-jwt-encoded-state-00
        $github = $adapterManager->get('github');
        $github->setCallbackUrl($callbackUrl);

        $fb = $adapterManager->get('facebook');
        $fb->setCallbackUrl($callbackUrl);
        
        $requestParams = $this->params()->fromQuery();

        $view = new ViewModel([
            'callbackUrl' => $serverHelper($this->url()->fromRoute('kap-security.oauth-callback', [], [
                'query' => [
                    'state' => $this->encodeJwt([
                        'requestParams' => $requestParams,
                        'service' => 'facebook_javascript'
                        ])
                    ]
            ])),
            'authorizationUrls' => [
                'github' => $github->getAuthenticationUrl($this->encodeJwt([
                        'requestParams' => $requestParams,
                        'service' => 'github'
                    ])),
                'facebook' => $fb->getAuthenticationUrl($this->encodeJwt([
                        'requestParams' => $requestParams,
                        'service' => 'facebook'
                    ])),
            ]
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

    protected function encodeJwt($payload) {
        $jwt = new Jwt();
        return $jwt->encode($payload, $this->jwtKey);
    }

    protected function decodeJwt($encoded) {
        $jwt = new Jwt();
        return $jwt->decode($encoded, $this->jwtKey);
    }
    
}
