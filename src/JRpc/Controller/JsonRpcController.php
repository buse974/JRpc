<?php

namespace JsonRpcServer\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use JsonRpcServer\Json\Server\Server;

class JsonRpcController extends AbstractActionController
{
    public function handleAction()
    {
    	$sm = $this->getServiceLocator();
    	
    	$config = $this->getServiceLocator()->get('config')['json-rpc-server'];
    	$cache = (isset($config['cache']) && is_string($config['cache'])) ? $sm->get($config['cache']) : null;
    	
        $server = $sm->get('json_server');
        $server->setReturnResponse(true);
        $server->getRequest()->setVersion(Server::VERSION_2);
        $server->setPersistence(true);
        $server->setCache($cache);
        $server->setArrayClass($config['services']);

        return $this->getResponse()->setContent($server->handle());
    }
}
