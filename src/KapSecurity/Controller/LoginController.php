<?php
namespace KapSecurity\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class LoginController extends AbstractActionController
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
        //$event = $this->getEvent();
        
        //$id = $this->params()->fromRoute('adapter');
        //$result = $this->authenticationService->authenticateById($id, $event);
        
        return [
            'callbackUrl' => $this->params()->fromQuery('callback_url')
        ];
    }
}
