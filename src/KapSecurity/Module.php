<?php
namespace KapSecurity;

use KapApigility\DbEntityRepository;
use KapSecurity\Authentication\Adapter\OAuth2;
use KapSecurity\Authentication\Adapter\CallbackAdapterInterface;
use KapSecurity\Authentication\AuthenticationService;
use KapSecurity\Authentication\Options;
use KapSecurity\V1\Rest\AuthenticationService\AuthenticationServiceEntity;
use KapSecurity\V1\Rest\IdentityAuthentication\IdentityAuthenticationResource;
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

        /**
         * [
        'name' => 'kap-security.authentication-callback',
        'params' => [
        'authentication_service_id' => $entity['id']
        ]
        ]
         */
        
        if ($entity instanceof AuthenticationServiceEntity) {
            $adapters = $this->sm->get('KapSecurity\Authentication\Adapter\AdapterManager');
            $adapter = $adapters->get($entity['system_adapter_service']);
            
            $helpers = $this->sm->get('ViewHelperManager');
            $serverUrl = $helpers->get('ServerUrl');
            $urlHelper = $helpers->get('Url');
            
            $url = $adapter->getRedirectUri();
            
            $callbackUrl = $serverUrl($urlHelper('kap-security.authentication-callback', [
                'authentication_service_id' => $entity['id']
            ]));
            $url = str_replace('AUTH_CALLBACK', urlencode($callbackUrl), $url);
            
            $halEntity->getLinks()->add(\ZF\Hal\Link\Link::factory(array(
                'rel' => 'redirect_url',
                'url' => $url,
            )));
        }
    }
    
    public function onAuthenticationPost(MvcAuthEvent $e)
    {
        /** @var AuthenticationService $authService */
        $authService = $this->sm->get('KapSecurity\Authentication\AuthenticationService');

        //not explicitly authenticated from apigility with known user session identity
        if($e->getIdentity() instanceof GuestIdentity && $authService->hasIdentity()) {
            $identityId = $authService->getIdentity();
            
            //todo this needs finishing - rbac permissions etc from what I understand rbac works like.
            $identity = new AuthenticatedIdentity($identityId);
            $identity->setName('user');
            
            $e->setIdentity($identity);
        }
    }

    public function getServiceConfig()
    {
        return [
            'aliases' => [
                'Zend\Authentication\AuthenticationService' => 'KapSecurity\Authentication\AuthenticationService'
            ],
            'factories' => [
                'KapSecurity\Authentication\Adapter\AdapterManager' => 'KapSecurity\Authentication\Adapter\AdapterManager',
                'KapSecurity\Authentication\AuthenticationService' => function($sm) {
                        $config = $sm->get('Config');
                        $options = empty($config['authentication_options']) ? [] : $config['authentication_options']; 
                        return new AuthenticationService(
                            new Options($options),
                            $sm->get('KapSecurity\\IdentityAuthenticationRepository'),
                            $sm->get('KapSecurity\\IdentityRepository'),
                            $sm->get('KapSecurity\\AuthenticationServiceRepository'),
                            $sm->get('KapSecurity\Authentication\Adapter\AdapterManager')
                        );
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
                'KapSecurity\\AuthenticationServiceRepository' => function($sm) {
                        $ins = new AuthenticationServiceRepository(
                            $sm->get('KapSecurity\V1\Rest\AuthenticationService\AuthenticationServiceResource\Table')
                        );
                        return $ins;
                    },
                "KapSecurity\\V1\\Rest\\AuthenticationService\\AuthenticationServiceResource" => function($sm) {
                        $ins = new \KapApigility\EntityRepositoryResource(
                            $sm->get('KapSecurity\\AuthenticationServiceRepository')
                        );
                        return $ins;
                    }
            ]
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
