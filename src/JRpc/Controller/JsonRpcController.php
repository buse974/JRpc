<?php

namespace JRpc\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use JRpc\Json\Server\Server;

class JsonRpcController extends AbstractActionController
{
    public function handleAction()
    {
        $headers = $this->getResponse()->getHeaders();

        $headers->addHeaderLine('Content-Type', 'application/json');

        $jrpcconfig = $this->getServiceLocator()->get('config')['json-rpc-server'];
        if (isset($jrpcconfig['headers'])) {
            foreach ($jrpcconfig['headers'] as $key => $value) {
                $headers->addHeaderLine($key, $value);
            }
        }

        if ('OPTIONS' === $this->getRequest()->getMethod()) {
            return $this->getResponse();
        }

        $server = $this->serviceLocator->get('json_server');
        $server->setReturnResponse(true);
        $server->getRequest()->setVersion(Server::VERSION_2);
        $server->initializeClass();

        $content = ('GET' === $this->getRequest()->getMethod()) ? $server->getServiceMap() : $server->handle();

        return $this->getResponse()->setContent($content);
    }
}
