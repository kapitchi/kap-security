<?php
namespace KapSecurity;

use KapApigility\DbEntityRepository;
use KapSecurity\Authentication\Adapter\CallbackAdapterInterface;
use KapSecurity\Authentication\AuthenticationService;
use KapSecurity\Authentication\Options;
use KapSecurity\V1\Rest\IdentityAuthentication\IdentityAuthenticationResource;
use Zend\Authentication\Storage\NonPersistent;
use Zend\Db\TableGateway\TableGateway;
use Zend\Mvc\MvcEvent;
use Zend\Paginator\Paginator;
use ZF\Apigility\Provider\ApigilityProviderInterface;
use ZF\MvcAuth\Identity\AuthenticatedIdentity;
use ZF\MvcAuth\Identity\GuestIdentity;
use ZF\MvcAuth\MvcAuthEvent;

class Module implements ApigilityProviderInterface
{
    protected $sm;
    
    public function onBootstrap($e)
    {
        $app = $e->getTarget();
        $this->sm = $app->getServiceManager();
        
        $events   = $app->getEventManager();
        $events->attach(MvcEvent::EVENT_RENDER, array($this, 'onRender'), 110);
        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION_POST, array($this, 'onAuthenticationPost'), -100);
    }
    
    public function onRender($e)
    {
        $helpers = $this->sm->get('ViewHelperManager');
        $hal = $helpers->get('hal');
        
        $hal->getEventManager()->attach(['renderEntity'], array($this, 'onRenderEntity'));
    }
    
    public function onRenderEntity($e)
    {
        $halEntity = $e->getParam('entity');
        $entity = $halEntity->entity;
        
        //TODO
    }
    
    public function onAuthenticationPost(MvcAuthEvent $e)
    {
        //TODO
    }
    
    public function getControllerConfig()
    {
        return [
            'aliases' => [
                'ZF\OAuth2\Controller\Auth' => 'KapSecurity\Controller\OAuthController'
            ],
            'factories' => [
                'KapSecurity\Controller\OAuthController' => function(\Zend\Mvc\Controller\ControllerManager $cm) {
                        $sm = $cm->getServiceLocator();
                        $server = $sm->get('ZF\OAuth2\Service\OAuth2Server');
                        $ins = new Controller\OAuthController($server);
                        return $ins;
                    }
            ]
        ];
    }

    /**
     * TODO move some to service factories
     * 
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'aliases' => [
                //for controller/view helpers to work
                'Zend\Authentication\AuthenticationService' => 'ZF\MvcAuth\Authentication',
                //'Zend\Authentication\AuthenticationService' => 'KapSecurity\Authentication\AuthenticationService',
                //'ZF\OAuth2\Adapter\PdoAdapter' => 'KapSecurity\OAuth2\PdoAdapter'
            ],
            'factories' => array(
                //'KapSecurity\OAuth2\PdoAdapter' => 'KapSecurity\OAuth2\PdoAdapter',
                'KapSecurity\OAuth2\PdoAdapter' => function($services) {
                        $config = $services->get('Config');

                        if (!isset($config['zf-oauth2']['db']) || empty($config['zf-oauth2']['db'])) {
                            throw new \RuntimeException(
                                'The database configuration [\'zf-oauth2\'][\'db\'] for OAuth2 is missing'
                            );
                        }

                        $username = isset($config['zf-oauth2']['db']['username']) ? $config['zf-oauth2']['db']['username'] : null;
                        $password = isset($config['zf-oauth2']['db']['password']) ? $config['zf-oauth2']['db']['password'] : null;
                        $options  = isset($config['zf-oauth2']['db']['options']) ? $config['zf-oauth2']['db']['options'] : array();

                        $oauth2ServerConfig = array();
                        if (isset($config['zf-oauth2']['storage_settings']) && is_array($config['zf-oauth2']['storage_settings'])) {
                            $oauth2ServerConfig = $config['zf-oauth2']['storage_settings'];
                        }

                        return new OAuth2\PdoAdapter(array(
                            'dsn'      => $config['zf-oauth2']['db']['dsn'],
                            'username' => $username,
                            'password' => $password,
                            'options'  => $options,
                        ), $oauth2ServerConfig);
                    },
                'KapSecurity\Authentication\Adapter\AdapterManager' => 'KapSecurity\Authentication\Adapter\AdapterManager',
                'KapSecurity\Authentication\AuthenticationService' => function($sm) {
                        $config = $sm->get('Config');
                        $options = empty($config['authentication_options']) ? [] : $config['authentication_options']; 
                        $ins = new AuthenticationService(
                            new Options($options),
                            $sm->get('KapSecurity\\IdentityAuthenticationRepository'),
                            $sm->get('KapSecurity\\IdentityRepository'),
                            $sm->get('KapSecurity\Authentication\Adapter\AdapterManager')
                        );
                        $ins->setStorage(new NonPersistent());
                        return $ins;
                    },
                'KapSecurity\\IdentityRepository' => function($sm) {
                        $ins = new IdentityRepository(
                            $sm->get('KapSecurity\V1\Rest\Identity\IdentityResource\Table')
                        );
                        return $ins;
                    },
                "KapSecurity\\V1\\Rest\\Identity\\IdentityResource" => function($sm) {
                        $ins = new \KapApigility\EntityRepositoryResource(
                            $sm->get('KapSecurity\\IdentityRepository')
                        );
                        return $ins;
                    },
                'KapSecurity\\IdentityAuthenticationRepository' => function($sm) {
                        $ins = new IdentityAuthenticationRepository(
                            $sm->get('KapSecurity\V1\Rest\IdentityAuthentication\IdentityAuthenticationResource\Table')
                        );
                        return $ins;
                    },
                "KapSecurity\\V1\\Rest\\IdentityAuthentication\\IdentityAuthenticationResource" => function($sm) {
                        $ins = new \KapApigility\EntityRepositoryResource(
                            $sm->get('KapSecurity\\IdentityAuthenticationRepository')
                        );
                        return $ins;
                    },
            )
        ];
    }
    
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'ZF\Apigility\Autoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }
}
