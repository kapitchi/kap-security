<?php

namespace KapSecurity\Authentication\Adapter;

use KapSecurity\Authentication\OAuth2Result;
use KapSecurity\Authentication\Result;
use KapSecurity\Authentication\UserProfile;
use League\OAuth2\Client\Entity\User;
use League\OAuth2\Client\Provider\AbstractProvider;
use Zend\Mvc\MvcEvent;

class OAuth2 implements AdapterInterface, RedirectionAdapterInterface {
    protected $authenticationService;
    protected $mvcEvent;
    /**
     * @var \League\OAuth2\Client\Provider\AbstractProvider
     */
    protected $provider;
    
    public function __construct($authenticationService, AbstractProvider $provider)
    {
        $this->authenticationService = $authenticationService;
        $this->provider = $provider;
    }
    
    /**
     * Performs an authentication attempt
     *
     * @return \Zend\Authentication\Result
     * @throws \Zend\Authentication\Adapter\Exception\ExceptionInterface If authentication cannot be performed
     */
    public function authenticate()
    {
        $code = $this->getMvcEvent()->getRequest()->getQuery('code');
        if(!$code) {
            return new Result(
                $this->authenticationService,
                Result::FAILURE_CREDENTIAL_INVALID,
                array(),
                array("No 'code' available")
            );
        }
        
        try {
            $provider = $this->getProvider();
            $token = $provider->getAccessToken('authorization_code', ['code' => $code]);
            
            $userProfile = $provider->getUserDetails($token);
            
            $res = new OAuth2Result(
                $this->authenticationService,
                Result::SUCCESS,
                $userProfile->uid
            );
            $res->setUserProfile($this->createUserProfile($userProfile));
            
            $res->setAccessToken($token->accessToken);
            $res->setRefreshToken($token->refreshToken);
            $res->setExpiresIn($token->expires);
            
            return $res;
            
        } catch(\Exception $e) {
            return new Result(
                $this->authenticationService,
                Result::FAILURE_CREDENTIAL_INVALID,
                $code,
                array($e->getMessage())
            );
        }
    }
    
    private function createUserProfile(User $user)
    {
        $orig = $user->getArrayCopy();
        $profile = new UserProfile();
        foreach([
            'nickname' => 'username',
            'name' => 'displayName',
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'email' => 'email',
            'description' => 'description',
            'imageUrl' => 'imageUrl',
                ] as $from => $to) {
            
            if(!empty($orig[$from])) {
                $profile[$to] = $orig[$from];
            }
        }
        
        return $profile;
    }

    public function getAuthenticationUrl($state)
    {
        return (string)$this->getProvider()->getAuthorizationUrl([
            'state' => $state
        ]);
    }
    
    /**
     * @param string $callbackUri
     */
    public function setCallbackUrl($callbackUri)
    {
        $this->getProvider()->redirectUri = $callbackUri;
    }

    /**
     * @param AbstractProvider $provider
     */
    public function setProvider(AbstractProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return AbstractProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param MvcEvent $mvcEvent
     */
    public function setMvcEvent(MvcEvent $mvcEvent = null)
    {
        $this->mvcEvent = $mvcEvent;
    }

    /**
     * @return MvcEvent
     */
    public function getMvcEvent()
    {
        return $this->mvcEvent;
    }

} 