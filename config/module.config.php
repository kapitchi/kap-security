<?php
return array(
    'view_manager' => array(
        'template_path_stack' => array(
            0 => __DIR__ . '/../view',
        ),
    ),
    'router' => array(
        'routes' => array(
            'kap-security.oauth-callback' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/oauth/callback',
                    'defaults' => array(
                        'controller' => 'KapSecurity\\Controller\\OAuthController',
                        'action' => 'callback',
                    ),
                ),
            ),
            'kap-security.rest.identity-authentication' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/identity_authentication[/:identity_authentication_id]',
                    'defaults' => array(
                        'controller' => 'KapSecurity\\V1\\Rest\\IdentityAuthentication\\Controller',
                    ),
                ),
            ),
            'kap-security.rest.identity' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/identity[/:identity_id]',
                    'defaults' => array(
                        'controller' => 'KapSecurity\\V1\\Rest\\Identity\\Controller',
                    ),
                ),
            ),
            'kap-security.rpc.me' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/me',
                    'defaults' => array(
                        'controller' => 'KapSecurity\\V1\\Rpc\\Me\\Controller',
                        'action' => 'me',
                    ),
                ),
            ),
        ),
    ),
    'zf-versioning' => array(
        'uri' => array(
            3 => 'kap-security.rest.identity-authentication',
            4 => 'kap-security.rest.identity',
            0 => 'kap-security.rpc.me',
        ),
    ),
    'zf-rest' => array(
        'KapSecurity\\V1\\Rest\\IdentityAuthentication\\Controller' => array(
            'listener' => 'KapSecurity\\V1\\Rest\\IdentityAuthentication\\IdentityAuthenticationResource',
            'route_name' => 'kap-security.rest.identity-authentication',
            'route_identifier_name' => 'identity_authentication_id',
            'collection_name' => 'identity_authentication',
            'entity_http_methods' => array(
                0 => 'GET',
                1 => 'PATCH',
                2 => 'PUT',
                3 => 'DELETE',
            ),
            'collection_http_methods' => array(
                0 => 'GET',
                1 => 'POST',
            ),
            'collection_query_whitelist' => array(),
            'page_size' => 25,
            'page_size_param' => null,
            'entity_class' => 'KapSecurity\\V1\\Rest\\IdentityAuthentication\\IdentityAuthenticationEntity',
            'collection_class' => 'KapSecurity\\V1\\Rest\\IdentityAuthentication\\IdentityAuthenticationCollection',
            'service_name' => 'identity_authentication',
        ),
        'KapSecurity\\V1\\Rest\\Identity\\Controller' => array(
            'listener' => 'KapSecurity\\V1\\Rest\\Identity\\IdentityResource',
            'route_name' => 'kap-security.rest.identity',
            'route_identifier_name' => 'identity_id',
            'collection_name' => 'identity',
            'entity_http_methods' => array(
                0 => 'GET',
                1 => 'PATCH',
                2 => 'PUT',
                3 => 'DELETE',
            ),
            'collection_http_methods' => array(
                0 => 'GET',
                1 => 'POST',
            ),
            'collection_query_whitelist' => array(),
            'page_size' => 25,
            'page_size_param' => null,
            'entity_class' => 'KapSecurity\\V1\\Rest\\Identity\\IdentityEntity',
            'collection_class' => 'KapSecurity\\V1\\Rest\\Identity\\IdentityCollection',
            'service_name' => 'identity',
        ),
    ),
    'zf-content-negotiation' => array(
        'controllers' => array(
            'KapSecurity\\V1\\Rest\\IdentityAuthentication\\Controller' => 'HalJson',
            'KapSecurity\\V1\\Rest\\Identity\\Controller' => 'HalJson',
            'KapSecurity\\V1\\Rpc\\Me\\Controller' => 'HalJson',
        ),
        'accept_whitelist' => array(
            'KapSecurity\\V1\\Rest\\IdentityAuthentication\\Controller' => array(
                0 => 'application/vnd.kap-security.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
            'KapSecurity\\V1\\Rest\\Identity\\Controller' => array(
                0 => 'application/vnd.kap-security.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ),
            'KapSecurity\\V1\\Rpc\\Me\\Controller' => array(
                0 => 'application/vnd.kap-security.v1+json',
                1 => 'application/json',
                2 => 'application/*+json',
            ),
        ),
        'content_type_whitelist' => array(
            'KapSecurity\\V1\\Rest\\IdentityAuthentication\\Controller' => array(
                0 => 'application/vnd.kap-security.v1+json',
                1 => 'application/json',
            ),
            'KapSecurity\\V1\\Rest\\Identity\\Controller' => array(
                0 => 'application/vnd.kap-security.v1+json',
                1 => 'application/json',
            ),
            'KapSecurity\\V1\\Rpc\\Me\\Controller' => array(
                0 => 'application/vnd.kap-security.v1+json',
                1 => 'application/json',
            ),
        ),
    ),
    'zf-hal' => array(
        'metadata_map' => array(
            'KapSecurity\\V1\\Rest\\IdentityAuthentication\\IdentityAuthenticationEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'kap-security.rest.identity-authentication',
                'route_identifier_name' => 'identity_authentication_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'KapSecurity\\V1\\Rest\\IdentityAuthentication\\IdentityAuthenticationCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'kap-security.rest.identity-authentication',
                'route_identifier_name' => 'identity_authentication_id',
                'is_collection' => true,
            ),
            'KapSecurity\\V1\\Rest\\Identity\\IdentityEntity' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'kap-security.rest.identity',
                'route_identifier_name' => 'identity_id',
                'hydrator' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
            ),
            'KapSecurity\\V1\\Rest\\Identity\\IdentityCollection' => array(
                'entity_identifier_name' => 'id',
                'route_name' => 'kap-security.rest.identity',
                'route_identifier_name' => 'identity_id',
                'is_collection' => true,
            ),
        ),
    ),
    'zf-apigility' => array(
        'db-connected' => array(
            'KapSecurity\\V1\\Rest\\IdentityAuthentication\\IdentityAuthenticationResource' => array(
                'adapter_name' => 'DefaultDbAdapter',
                'table_name' => 'identity_authentication',
                'hydrator_name' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
                'controller_service_name' => 'KapSecurity\\V1\\Rest\\IdentityAuthentication\\Controller',
                'entity_identifier_name' => 'id',
            ),
            'KapSecurity\\V1\\Rest\\Identity\\IdentityResource' => array(
                'adapter_name' => 'DefaultDbAdapter',
                'table_name' => 'identity',
                'hydrator_name' => 'Zend\\Stdlib\\Hydrator\\ArraySerializable',
                'controller_service_name' => 'KapSecurity\\V1\\Rest\\Identity\\Controller',
                'entity_identifier_name' => 'id',
            ),
        ),
    ),
    'controllers' => array(
        'factories' => array(
            'KapSecurity\\V1\\Rpc\\Me\\Controller' => 'KapSecurity\\V1\\Rpc\\Me\\MeControllerFactory',
        ),
    ),
    'zf-rpc' => array(
        'KapSecurity\\V1\\Rpc\\Me\\Controller' => array(
            'service_name' => 'Me',
            'http_methods' => array(
                0 => 'GET',
            ),
            'route_name' => 'kap-security.rpc.me',
        ),
    ),
    'service_manager' => array(
        'factories' => array(),
    ),
    'zf-mvc-auth' => array(
        'authorization' => array(
            'KapSecurity\\V1\\Rest\\IdentityAuthentication\\Controller' => array(
                'entity' => array(
                    'GET' => true,
                    'POST' => false,
                    'PATCH' => true,
                    'PUT' => true,
                    'DELETE' => true,
                ),
                'collection' => array(
                    'GET' => true,
                    'POST' => true,
                    'PATCH' => false,
                    'PUT' => false,
                    'DELETE' => false,
                ),
            ),
            'KapSecurity\\V1\\Rest\\Identity\\Controller' => array(
                'entity' => array(
                    'GET' => true,
                    'POST' => false,
                    'PATCH' => true,
                    'PUT' => true,
                    'DELETE' => true,
                ),
                'collection' => array(
                    'GET' => true,
                    'POST' => true,
                    'PATCH' => false,
                    'PUT' => false,
                    'DELETE' => false,
                ),
            ),
            'KapSecurity\\V1\\Rpc\\Me\\Controller' => array(
                'actions' => array(
                    'me' => array(
                        'GET' => true,
                        'POST' => false,
                        'PATCH' => false,
                        'PUT' => false,
                        'DELETE' => false,
                    ),
                ),
            ),
        ),
    ),
);
