<?php
namespace KapSecurity\V1\Rpc\Authenticate;

use KapSecurity\Authentication\Adapter\AdapterManager;
use Zend\Authentication\AuthenticationServiceInterface;
use Zend\Authentication\Result;
use Zend\Mvc\Controller\AbstractActionController;

class AuthenticateController extends AbstractActionController
{
    /**
     * @var AuthenticationServiceInterface
     */
    protected $authenticationService;

    /**
     * @var AdapterManager
     */
    protected $adapterManager;

    public function __construct($authenticationService, $adapterManager)
    {
        $this->authenticationService = $authenticationService;
        $this->adapterManager = $adapterManager;
    }

    public function authenticateAction()
    {
        $event = $this->getEvent();
        
        //$type = $this->bodyParams();
        
        $type = 'facebook_javascript';
        
        $adapter = $this->adapterManager->get($type);
        
        $result = $this->authenticationService->authenticate($adapter);
        return $this->createResponse($result);
    }

    private function createResponse(Result $result)
    {
        return [
            'code' => $result->getCode(),
            'identityId' => $result->getIdentityId(),
            'userProfile' => $result->getUserProfile() ? $result->getUserProfile()->getArrayCopy() : null,
            'isValid' => $result->isValid(),
            'identity' => $result->getIdentity(),
            'messages' => $result->getMessages()
        ];

    }
}
