<?php

namespace KapSecurity\Authentication;

use KapSecurity\Authentication\Adapter\AdapterInterface;
use KapSecurity\AuthenticationServiceRepository;
use KapSecurity\IdentityAuthenticationRepository;
use KapSecurity\IdentityRepository;
use KapSecurity\V1\Rest\IdentityAuthentication\IdentityAuthenticationResource;
use Zend\Authentication\Exception;
use Zend\Mvc\MvcEvent;
use ZF\Rest\AbstractResourceListener;
use OAuth2\Encryption\Jwt;

class AuthenticationService extends \Zend\Authentication\AuthenticationService {
    
    protected $options;
    protected $identityAuthenticationRepository;
    protected $identityRepository;
    
    public function __construct(Options $options, IdentityAuthenticationRepository $identityAuthenticationRepository,
                                IdentityRepository $identityRepository)
    {
        $this->options = $options;
        $this->identityAuthenticationRepository = $identityAuthenticationRepository;
        $this->identityRepository = $identityRepository;
    }
    
    public function authenticate(AdapterInterface $adapter = null)
    {
        if (!$adapter) {
            if (!$adapter = $this->getAdapter()) {
                throw new Exception\RuntimeException('An adapter must be set or passed prior to calling authenticate()');
            }
        }
        $result = $adapter->authenticate();

        /**
         * ZF-7546 - prevent multiple successive calls from storing inconsistent results
         * Ensure storage has clean state
         */
        if ($this->hasIdentity()) {
            $this->clearIdentity();
        }

        if($result->isValid() && $result instanceof Result) {
            $serviceId = $result->getAuthenticationService();
            $identity = $result->getIdentity();

            $data = array(
                'authentication_service' => $serviceId,
                'identity' => $identity
            );

            $authEntity = $this->identityAuthenticationRepository->getPaginatorAdapter($data)->getItems(0, 1)->current();

            $identityEntity = null;
            if(!$authEntity) {
                if(!$this->options->getAllowRegistration()) {
                    $result->setCode(Result::FAILURE_REGISTRATION_DISABLED);
                    return $result;
                }

                $enable = $this->options->getEnableOnRegistration();

                $idData = [
                    'enabled' => $enable,
                    'authentication_enabled' => $enable,
                    'registered_time' => date('Y-m-d H:i:s')
                ];
                
                $profile = $result->getUserProfile();
                if($profile) {
                    $idData['display_name'] = $profile['displayName'];
                    $data['user_profile_json'] = json_encode((array)$profile);
                }
                
                $identityEntity = $this->identityRepository->create($idData);
                
                $data['owner_id'] = $identityEntity['id'];
                $authEntity = $this->identityAuthenticationRepository->create($data);
            }
            
            if(!$identityEntity) {
                $identityEntity = $this->identityRepository->find($authEntity['owner_id']);
            }
            
            if(!$identityEntity['authentication_enabled']) {
                $result->setCode(Result::FAILURE_IDENTITY_DISABLED);
                return $result;
            }
            
            $result->setIdentityId($authEntity['owner_id']);
            
            $this->getStorage()->write($authEntity['owner_id']);
        }
        
        return $result;
    }

    public function encodeJwt($payload) {
        $jwt = new Jwt();
        return $jwt->encode($payload, $this->getJwtKey());
    }

    public function decodeJwt($encoded) {
        $jwt = new Jwt();
        return $jwt->decode($encoded, $this->getJwtKey());
    }
    
    private function getJwtKey()
    {
        $key = $this->options->getJwtKey();
        if(empty($key)) {
            throw new \RuntimeException("No JWT key specified in options \$config['authentication_options']['jwt_key']");
        }
        
        return $key;
    }
    
} 