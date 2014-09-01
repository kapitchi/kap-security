<?php
/**
 * Created by PhpStorm.
 * User: zemi
 * Date: 12/05/14
 * Time: 19:34
 */

namespace KapSecurity\Authentication;


class Result extends \Zend\Authentication\Result {
    /**
     * Authenticated (e.g. facebook) but not registered in the system yet and registration is disabled
     */
    const FAILURE_REGISTRATION_DISABLED = -5;
    const FAILURE_IDENTITY_DISABLED = -6;

    protected $userProfile;
    protected $identityId;
    protected $authenticationService;

    public function __construct($authenticationService, $code, $identity, array $messages = array())
    {
        parent::__construct($code, $identity, $messages);
        $this->setAuthenticationService($authenticationService);
    }
    
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @param string $authenticationService
     * 
     * Example: LOCAL, FACEBOOK, ...
     */
    public function setAuthenticationService($authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    /**
     * @return string
     */
    public function getAuthenticationService()
    {
        return $this->authenticationService;
    }

    public function setIdentityId($identityId)
    {
        $this->identityId = $identityId;
    }

    /**
     * @return int
     */
    public function getIdentityId()
    {
        return $this->identityId;
    }

    /**
     * @param UserProfile $userProfile
     */
    public function setUserProfile(UserProfile $userProfile)
    {
        $this->userProfile = $userProfile;
    }

    /**
     * @return UserProfile
     */
    public function getUserProfile()
    {
        return $this->userProfile;
    }

} 