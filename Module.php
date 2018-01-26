<?php
namespace JRpc;

use \Zend\ModuleManager\Feature\ConfigProviderInterface;
use \JRpc\Json\Server\Server;

class Module implements ConfigProviderInterface
{

    public function getAutoloaderConfig()
    {
        return array('Zend\Loader\StandardAutoloader' => array('namespaces' => array(__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__)));
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return [
            'aliases' => [
                'json_server' => Json\Server\Server::class
            ],
            'factories' => [
                Json\Server\Server::class => function ($container, $requestedName, $options) {
                    return  new Server($container, $container->get('config')['json-rpc-server']);
                },
            ],
        ];
    }
}
