<?php
namespace KapSecurity\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class AuthenticationController extends AbstractActionController
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
    
    public function loginAction()
    {
        $event = $this->getEvent();
        
        $id = $this->params()->fromRoute('authentication_service_id');
        $result = $this->authenticationService->authenticateById($id, $event);
        
        return [
            'result' => $result
        ];
    }
}
