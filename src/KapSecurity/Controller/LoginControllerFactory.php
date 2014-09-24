<?php
namespace KapSecurity\Controller;

class AuthenticationControllerFactory
{
    public function __invoke($controllers)
    {
        $sm = $controllers->getServiceLocator();
        $ins = new AuthenticationController(
            $sm->get('KapSecurity\Authentication\AuthenticationService')
            $sm->get('KapSecurity\Authentication\Adapter\AdapterManager')
        );
        return $ins;
    }
}
