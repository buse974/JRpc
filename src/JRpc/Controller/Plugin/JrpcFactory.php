<?php
/**
 * 
 * TagnCar (http://tagncar.com)
 *
 * JrpcFactory
 *
 */
namespace JRpc\Controller\Plugin;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class JrpcFactory implements FactoryInterface
{

    /**
     * 
     * {@inheritDoc}
     * @see \Zend\ServiceManager\Factory\FactoryInterface::__invoke()
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['json-rpc-server'];
        
        return new Jrpc($config, $container->get('json_server'));
    }
}