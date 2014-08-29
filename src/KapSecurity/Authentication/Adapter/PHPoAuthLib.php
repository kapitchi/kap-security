<?php

namespace KapSecurity\Authentication\Adapter;

use KapSecurity\Authentication\OAuth2Result;
use KapSecurity\Authentication\Result;
use KapSecurity\Authentication\UserProfile;
use OAuth\OAuth2\Service\ServiceInterface;
use OAuth\UserData\Extractor\ExtractorInterface;
use OAuth\UserData\ExtractorFactory;
use Zend\Mvc\MvcEvent;

class PHPoAuthLib implements AdapterInterface, CallbackAdapterInterface {
    protected $id;
    protected $code;
    protected $mvcEvent;
    protected $service;
    protected $callbackUri;
    
    public function __construct($id, ServiceInterface $service)
    {
        $this->id = $id;
        $this->service = $service;
    }
    
    public function getId()
    {
        return $this->id;
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
                Result::FAILURE_CREDENTIAL_INVALID,
                array(),
                array("No 'code' available")
            );
        }
        
        try {
            $service = $this->getService();
            $token = $service->requestAccessToken($code);
            
            $extractorFactory = new ExtractorFactory();
            $extractor = $extractorFactory->get($service);

            $res = new OAuth2Result(
                Result::SUCCESS,
                $extractor->getUniqueId()
            );
            $res->setUserProfile($this->createUserProfile($extractor));
            
            $res->setAccessToken($token->getAccessToken());
            $res->setRefreshToken($token->getRefreshToken());
            $res->setExpiresIn($token->getEndOfLife());
            
            return $res;
            
        } catch(\Exception $e) {
            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                $code,
                array($e->getMessage())
            );
        }
    }
    
    private function createUserProfile(ExtractorInterface $extractor)
    {
        $profile = new UserProfile();
        $profile['username'] = $extractor->getUsername(); 
        $profile['displayName'] = $extractor->getFullName(); 
        $profile['firstName'] = $extractor->getFirstName();
        $profile['lastName'] = $extractor->getLastName();
        $profile['email'] = $extractor->getEmail();
        $profile['description'] = $extractor->getDescription();
        $profile['imageUrl'] = $extractor->getImageUrl();
        $profile['profileUrl'] = $extractor->getProfileUrl();
        //$profile['websites'] = $extractor->getWebsites();
        
        return $profile;
    }

    public function getRedirectUri()
    {
        return (string)$this->getService()->getAuthorizationUri();
    }
    
    /**
     * @param string $callbackUri
     */
    public function setCallbackUri($callbackUri)
    {
        $this->getService()->redirectUri = $callbackUri;
    }

    /**
     * @param AbstractProvider $service
     */
    public function setService(AbstractProvider $service)
    {
        $this->service = $service;
    }

    /**
     * @return AbstractProvider
     */
    public function getService()
    {
        return $this->service;
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