<?php

namespace JRpc;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Zend\Diactoros\Response\TextResponse;

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
                'Action\JrpcAction' => function ( $container ) {
                    return function ($request, DelegateInterface $delegate) use ($container) {
                        $method  = $request->getMethod();
                        $headers = [];
                        $jrpcconfig = $container->get('config')['json-rpc-server'];
                        if('POST' === $method || ('GET' === $method && $jrpcconfig['environment'] === 'dev')) {
                            $server = $container->get(Json\Server\Server::class);
                            $server->setReturnResponse(true);
                            $server->initializeClass();
                            
                            $headers = ['Content-Type' => 'application/json'];
                            $content = ('POST' === $method) ? $server->multiHandle() : $server->getServiceMap()->toArray();
                        } else {
                            $content = "";
                        }

                        return new TextResponse((string) $content , 200, $headers);
                    };
                },
            ],
        ];
    }
    
    public function getRoutes() : array
    {
        return [
            [
                'name'            => 'jrpc',
                'path'            => '/api-json.rpc',
                'middleware'      => 'Action\JrpcAction',
                'allowed_methods' => ['POST', 'GET', 'OPTIONS'],
            ],
        ];
    }
}
