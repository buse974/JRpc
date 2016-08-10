<?php

namespace JRpc\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use JRpc\Json\Server\Server;

class JsonRpcController extends AbstractActionController
{
    public function handleAction()
    {
        $method = $this->getRequest()->getMethod();
        $jrpcconfig = $this->getServiceLocator()->get('config')['json-rpc-server'];
        if (isset($jrpcconfig['headers'])) {
            foreach ($jrpcconfig['headers'] as $key => $value) {
                $headers->addHeaderLine($key, $value);
            }
        }

        if('POST' === $method || ('GET' === $method && $jrpcconfig['environment'] === 'dev')) {
            $server = $this->serviceLocator->get('json_server');
            $server->setReturnResponse(true);
            $server->initializeClass();
            
            $headers = $this->getResponse()->getHeaders();
            $headers->addHeaderLine('Content-Type', 'application/json');
            
            $content = ('POST' === $method) ? $server->multiHandle() : $server->getServiceMap();
        } else {
            $content = "";
        }
        
        return $this->getResponse()->setContent($content);
    }
}
