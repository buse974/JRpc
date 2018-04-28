<?php

namespace JRpc\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\TextResponse;

class JrpcAction implements RequestHandlerInterface
{
    protected $headers;
    protected $json_server;
    protected $jrpc_config;
    
    public function __construct($json_server, $jrpc_config, $headers)
    {
        $this->json_server = $json_server;
        $this->jrpc_config = $jrpc_config;
        $this->headers = $headers;
    }
    
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $content = "";
        $method  = $request->getMethod();
        
        if('POST' === $method || ('GET' === $method && $this->jrpc_config['environment'] === 'dev')) {
            $this->json_server->setReturnResponse(true);
            $this->json_server->initializeClass();
            $this->headers = array_merge($this->headers, ['Content-Type' => 'application/json']);
            $content = ('POST' === $method) ? $this->json_server->multiHandle() : $this->json_server->getServiceMap();
        } else {
            $content = "";
        }
        
        return new TextResponse((string) $content , 200, $this->headers);
    }

}
