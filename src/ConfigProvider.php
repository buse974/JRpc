<?php

namespace JRpc;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array
     */
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'routes'       => $this->getRoutes(),
            'json-rpc-server' => [
                'cache' => 'storage_memcached',
                'log' => 'log-system',
                'persistence' => false,
                'environment' => 'prod', /* dev|prod */
                'services' => [],
            ],
        ];
    }

    /**
     * Returns the container dependencies
     *
     * @return array
     */
    public function getDependencies() : array
    {
        return [
            'factories'  => [
                Json\Server\Server::class => function ( $container ) {
                    return new Json\Server\Server($container, $container->get('config')['json-rpc-server']);
                },
                Action\JrpcAction::class => function ( $container ) {
                    $jrpc_config = $container->get('config')['json-rpc-server'];
                    $headers = $jrpc_config['headers'];
                    $server = $container->get(Json\Server\Server::class);
                    
                    return new Action\JrpcAction($server, $jrpc_config, $headers);
                },
            ],
        ];
    }
    
    public function getRoutes() : array
    {
        return [
            [
                'name'            => 'jrpc',
                'path'            => '/api.json-rpc',
                'middleware'      => Action\JrpcAction::class,
                'allowed_methods' => ['POST', 'GET', 'OPTIONS'],
            ],
        ];
    }
}
