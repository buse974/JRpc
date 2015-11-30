<?php

return array(
    'view_manager' => array(
        'strategies' => array(
                'ViewJsonStrategy',
        ),
    ),
    'service_manager' => array(
        'invokables' => array(
            'json_server' => 'JRpc\Json\Server\Server',
        ),
    ),
    'router' => array(
        'routes' => array(
            'api.json-rpc' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/api.json-rpc',
                    'defaults' => array(
                        'controller' => 'json_rpc',
                        'action' => 'handle',
                    ),
                ),
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'json_rpc' => 'JRpc\Controller\JsonRpcController',
        ),
    ),
    'json-rpc-server' => array(
        'cache' => 'storage_memcached',
        'log' => 'log-system',
        'persistence' => false,
        'services' => array(
        ),
    ),
);
