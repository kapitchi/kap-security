<?php
/**
 * Kapitchi Zend Framework 2 Modules
 *
 * @copyright Copyright (c) 2012-2014 Kapitchi Open Source Community (http://kapitchi.com/open-source)
 * @license   http://opensource.org/licenses/MIT MIT
 */

namespace KapSecurity\Authentication;


use Zend\Stdlib\AbstractOptions;

class Options extends AbstractOptions {
    protected $allowRegistration = false;
    protected $enableOnRegistration = true;
    protected $jwtKey = null;
    protected $callbackUrl = null;//e.g. http://example.com/oauth/callback

    /**
     * @param boolean $allowRegistration
     */
    public function setAllowRegistration($allowRegistration)
    {
        $this->allowRegistration = $allowRegistration;
    }

    /**
     * @return boolean
     */
    public function getAllowRegistration()
    {
        return $this->allowRegistration;
    }

    /**
     * @param boolean $enableOnRegistration
     */
    public function setEnableOnRegistration($enableOnRegistration)
    {
        $this->enableOnRegistration = $enableOnRegistration;
    }

    /**
     * @return boolean
     */
    public function getEnableOnRegistration()
    {
        return $this->enableOnRegistration;
    }

    /**
     * @param string $jwtKey
     */
    public function setJwtKey($jwtKey)
    {
        $this->jwtKey = $jwtKey;
    }

    /**
     * @return string
     */
    public function getJwtKey()
    {
        return $this->jwtKey;
    }

    /**
     * @param string $callbackUrl
     */
    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }
}