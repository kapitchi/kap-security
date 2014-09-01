<?php

namespace KapSecurity\Authentication\Adapter;

use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookSession;
use Facebook\GraphUser;
use KapSecurity\Authentication\OAuth2Result;
use KapSecurity\Authentication\Result;
use KapSecurity\Authentication\UserProfile;
use Zend\Mvc\MvcEvent;

class Facebook implements AdapterInterface, CallbackAdapterInterface {
    protected $id;
    protected $mvcEvent;
    protected $callbackUri;
    protected $service;
    
    public function __construct(array $options)
    {
        \Facebook\FacebookSession::setDefaultApplication($options['clientId'], $options['clientSecret']);
    }
    
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
    
    public function getService()
    {
        return $this->service;
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
            $service = $this->service;

            try {
                $session = $service->getSessionFromRedirect();
            } catch(FacebookRequestException $ex) {
                // When Facebook returns an error
                return new Result(
                    Result::FAILURE_CREDENTIAL_INVALID,
                    array(),
                    array("Facebook request exception - " . $ex->getErrorType() . " - " . $ex->getMessage())
                );
            } catch(\Exception $ex) {
                // When validation fails or other local issues
                return new Result(
                    Result::FAILURE,
                    array(),
                    array("General exception - " . $ex->getMessage())
                );
            }

            $me = (new FacebookRequest(
                $session, 'GET', '/me'
            ))->execute()->getGraphObject(GraphUser::className());
            
            $res = new OAuth2Result(
                Result::SUCCESS,
                $me->getId()
            );
            $res->setUserProfile($this->createUserProfile($session, $me));
            
            //TODO
            //$res->setAccessToken($token->accessToken);
            //$res->setRefreshToken($token->refreshToken);
            //$res->setExpiresIn($token->expires);
            
            return $res;
            
        } catch(\Exception $e) {
            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                $code,
                array($e->getMessage())
            );
        }
    }
    
    private function createUserProfile(FacebookSession $session, GraphUser $user)
    {
        $profile = new UserProfile();
        
        $profilePicReq = new FacebookRequest($session, 'GET', '/me/picture', [
            'redirect' => 0,
            'type' => 'large',
            //'width' => 1920,
            //'height' => 1080
        ]);
        $pic = $profilePicReq->execute()->getGraphObject()->asArray();
        
        $profile['displayName'] = $user->getName();
        $profile['profileUrl'] = $user->getLink();
        $profile['imageUrl'] = $pic['url'];
        //TODO other props
        
        return $profile;
    }

    public function getRedirectUri()
    {
        $service = $this->getService();
        return $service->getLoginUrl();
    }
    
    /**
     * @param string $callbackUri
     */
    public function setCallbackUri($callbackUri)
    {
        $this->service = new \Facebook\FacebookRedirectLoginHelper($callbackUri);
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