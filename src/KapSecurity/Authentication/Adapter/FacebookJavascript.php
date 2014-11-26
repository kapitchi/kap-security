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

class FacebookJavascript implements AdapterInterface {
    protected $authenticationService = 'FACEBOOK';
    protected $service;
    
    public function __construct(array $options)
    {
        if(empty($options['clientId']) || empty($options['clientSecret'])) {
            throw new \InvalidArgumentException("\$options['clientId'] and \$options['clientSecret'] need to be set");
        }
        
        \Facebook\FacebookSession::setDefaultApplication($options['clientId'], $options['clientSecret']);
        $this->service = new \Facebook\FacebookJavaScriptLoginHelper();
    }
    
    /**
     * Performs an authentication attempt
     *
     * @return \Zend\Authentication\Result
     * @throws \Zend\Authentication\Adapter\Exception\ExceptionInterface If authentication cannot be performed
     */
    public function authenticate()
    {
        try {
            $service = $this->service;

            $session = $service->getSession();
            
            $me = (new FacebookRequest(
                $session, 'GET', '/me'
            ))->execute()->getGraphObject(GraphUser::className());
            
            $res = new OAuth2Result(
                $this->authenticationService,
                Result::SUCCESS,
                $me->getId()
            );
            $res->setUserProfile($this->createUserProfile($session, $me));
            
            //TODO
            //$res->setAccessToken($token->accessToken);
            //$res->setRefreshToken($token->refreshToken);
            //$res->setExpiresIn($token->expires);
            
            return $res;

        } catch(FacebookRequestException $ex) {
            // When Facebook returns an error
            return new Result(
                $this->authenticationService,
                Result::FAILURE_CREDENTIAL_INVALID,
                array(),
                array("Facebook request exception - " . $ex->getErrorType() . " - " . $ex->getMessage())
            );
        } catch(\Exception $ex) {
            // When validation fails or other local issues
            return new Result(
                $this->authenticationService,
                Result::FAILURE,
                array(),
                array("General exception - " . $ex->getMessage())
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

} 