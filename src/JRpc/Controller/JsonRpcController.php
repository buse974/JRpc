<?php

namespace JRpc\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use JRpc\Json\Server\Server;

class JsonRpcController extends AbstractActionController
{
    public function handleAction()
    {
        $server = $this->serviceLocator->get('json_server');
        $server->setReturnResponse(true);
        $server->getRequest()->setVersion(Server::VERSION_2);
        $server->initializeClass();

        $content = ('GET' == $this->getRequest()->getMethod()) ? $server->getServiceMap() : $server->handle();

        return $this->getResponse()->setContent($content);
    }
}
