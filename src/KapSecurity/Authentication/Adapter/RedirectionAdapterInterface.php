<?php
/**
 * Kapitchi Zend Framework 2 Modules
 *
 * @copyright Copyright (c) 2012-2014 Kapitchi Open Source Community (http://kapitchi.com/open-source)
 * @license   http://opensource.org/licenses/MIT MIT
 */

namespace KapSecurity\Authentication\Adapter;


interface RedirectionAdapterInterface {
    public function setCallbackUrl($url);
    public function getAuthenticationUrl($state);
} 