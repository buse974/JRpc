<?php

namespace JRpc\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class JsonRpcController extends AbstractActionController
{
    public function handleAction()
    {
        $headers = $this->getResponse()->getHeaders();
        $method = $this->getRequest()->getMethod();
        
        $jrpcconfig =  $this->jrpc()->getSettings();
        if (isset($jrpcconfig['headers'])) {
            foreach ($jrpcconfig['headers'] as $key => $value) {
                if(is_callable($value)) {
                    $headers->addHeaderLine($key, $value());
                } else {
                    $headers->addHeaderLine($key, $value);
                }
            }
        }
        
        if('POST' === $method || ('GET' === $method && $jrpcconfig['environment'] === 'dev')) {
            $server = $this->jrpc()->getServer();
            $server->setReturnResponse(true);
            $server->initializeClass();
            $headers->addHeaderLine('Content-Type', 'application/json');
            $content = ('POST' === $method) ? $server->multiHandle() : $server->getServiceMap();
        } else {
            $content = "";
        }
        
        return $this->getResponse()->setContent($content);
    }
}
