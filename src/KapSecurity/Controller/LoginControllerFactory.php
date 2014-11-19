<?php
namespace KapSecurity\Controller;

class LoginControllerFactory
{
    public function __invoke($controllers)
    {
        $sm = $controllers->getServiceLocator();
        $ins = new LoginController(
            $sm->get('KapSecurity\Authentication\AuthenticationService'),
            $sm->get('KapSecurity\Authentication\Adapter\AdapterManager')
        );
        return $ins;
    }
}
