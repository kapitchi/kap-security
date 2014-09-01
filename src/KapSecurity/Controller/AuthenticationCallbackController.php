<?php
namespace KapSecurity\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class AuthenticationCallbackController extends AbstractActionController
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
    
    public function authenticationCallbackAction()
    {
        $event = $this->getEvent();
        
        $id = $this->params()->fromRoute('authentication_service_id');
        $result = $this->authenticationService->authenticateById($id, $event);
        
        return [
            'result' => $result
        ];
    }
}
