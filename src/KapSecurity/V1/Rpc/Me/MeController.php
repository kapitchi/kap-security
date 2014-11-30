<?php
namespace KapSecurity\V1\Rpc\Me;

use Zend\Mvc\Controller\AbstractActionController;
use ZF\ContentNegotiation\ViewModel;
use ZF\Hal\Entity;

class MeController extends AbstractActionController
{
    /**
     * TODO checks 
     * @return ViewModel
     */
    public function meAction()
    {
        $event = $this->getEvent();
        $identity = $event->getParam('ZF\MvcAuth\Identity');
        $id = $identity->getName();
        
        $identity = $this->getServiceLocator()->get('KapSecurity\IdentityRepository')->find($id);

        return new ViewModel(array(
            'payload' => new Entity($identity, $identity['id']),
        ));
    }
}
