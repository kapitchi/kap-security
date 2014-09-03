<?php
/**
 * Created by PhpStorm.
 * User: zemi
 * Date: 11/05/14
 * Time: 15:18
 */

namespace KapSecurity\Authentication\Adapter;


use Zend\Mvc\Exception\InvalidPluginException;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\Exception;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AdapterManager extends AbstractPluginManager implements FactoryInterface {
    
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        
        $managerConfig = [];
        if(!empty($config['authentication_adapter_manager']) && !empty($config['authentication_adapter_manager']['adapters'])) {
            $managerConfig = $config['authentication_adapter_manager']['adapters'];
        }
        
        $ins = new self(new Config($managerConfig));
        $ins->setServiceLocator($serviceLocator);
        return $ins;
    }
    
    /**
     * Validate the plugin
     *
     * Checks that the filter loaded is either a valid callback or an instance
     * of FilterInterface.
     *
     * @param  mixed $plugin
     * @return void
     * @throws Exception\RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        if($plugin instanceof AdapterInterface) {
            return;
        }

        throw new InvalidPluginException(sprintf(
            'Plugin of type %s is invalid; must implement %s\AdapterInterface and set ID',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
            __NAMESPACE__
        ));
    }

    public function get($name, $options = array(), $usePeeringServiceManagers = true)
    {
        $ins = parent::get($name, $options, $usePeeringServiceManagers);

        //$ins->setId($this->canonicalizeName($name));

//        if(method_exists($ins, 'setMvcEvent')) {
//            $ins->setMvcEvent($this->getMvcEvent());
//        }

        return $ins;
    }
} 