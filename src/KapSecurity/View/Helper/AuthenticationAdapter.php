<?php
/**
 * Kapitchi Zend Framework 2 Modules
 *
 * @copyright Copyright (c) 2012-2014 Kapitchi Open Source Community (http://kapitchi.com/open-source)
 * @license   http://opensource.org/licenses/MIT MIT
 */ 

namespace KapSecurity\View\Helper;

use KapSecurity\Authentication\Adapter\AdapterManager;
use KapSecurity\Authentication\Adapter\RedirectionAdapterInterface;
use KapSecurity\Authentication\AuthenticationService;
use Zend\View\Helper\AbstractHelper;

class AuthenticationAdapter extends AbstractHelper
{
    protected $adapterManager;
    protected $authenticationService;
    
    public function __construct(AdapterManager $adapterManager, AuthenticationService $authenticationService)
    {
        $this->adapterManager = $adapterManager;
        $this->authenticationService = $authenticationService;
    }
    
    public function getCallbackUrl($service, array $queryParams)
    {
        $params = [
            'service' => $service,
            'requestParams' => $queryParams
        ];
        
        $adapter = $this->adapterManager->get($service);
        if($adapter instanceof RedirectionAdapterInterface) {
            return $adapter->getAuthenticationUrl($this->authenticationService->encodeJwt($params));
        }
        
        $serverHelper = $this->getView()->plugin('serverUrl');
        return $serverHelper($this->getView()->url('kap-security.oauth-callback', [], [
            'query' => [
                'state' => $this->authenticationService->encodeJwt($params)
            ]
        ]));
    }
    
}