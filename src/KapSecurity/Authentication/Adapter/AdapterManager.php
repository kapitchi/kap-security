<?php
/**
 * Created by PhpStorm.
 * User: zemi
 * Date: 11/05/14
 * Time: 15:18
 */

namespace KapSecurity\Authentication\Adapter;


use KapSecurity\Authentication\Options;
use Zend\Mvc\Exception\InvalidPluginException;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\Exception;
use Zend\ServiceManager\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AdapterManager extends AbstractPluginManager implements InitializerInterface {
    
    protected $options;
    
    public function __construct(Config $config, Options $options) {
        parent::__construct($config);
        
        $this->options = $options;
        
        $this->addInitializer($this);
    }

    public function initialize($instance, ServiceLocatorInterface $serviceLocator)
    {
        if($instance instanceof RedirectionAdapterInterface) {
            if(!$this->options->getCallbackUrl()) {
                throw new \RuntimeException("Callback url is not set on authentication options");
            }
            $instance->setCallbackUrl($this->options->getCallbackUrl());
        }
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

} 