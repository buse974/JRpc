<?php

namespace JRpcTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class JsonRpcControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(include __DIR__.'/../../config/application.config.php');
        parent::setUp();
    }

    public function testHandleActionPost()
    {
        $mock_server = $this->getMockBuilder('JRpc\Json\Server\Server')
            ->setMethods(array('setReturnResponse', 'initializeClass', 'getServiceMap', 'multiHandle'))
            ->getMock();

        $mock_server->expects($this->once())
            ->method('setReturnResponse')
            ->with(true);

        $mock_server->expects($this->once())
            ->method('initializeClass');

        $mock_server->expects($this->never())
            ->method('getServiceMap')
            ->will($this->returnValue(array('getServiceMap')));

        $mock_server->expects($this->once())
            ->method('multiHandle')
            ->will($this->returnValue(array('multiHandle')));

        $sm = $this->getApplicationServiceLocator()->setAllowOverride(true);
        $sm->setService('json_server', $mock_server);

        $this->dispatch('/api.json-rpc', 'POST');

        $this->assertControllerName('json_rpc');
        $this->assertActionName('handle');
        $this->assertResponseStatusCode(200);

        $this->assertEquals('multiHandle', current($this->getResponse()
            ->getContent()));
    }

    public function testHandleActionOptions()
    {
        $mock_server = $this->getMockBuilder('JRpc\Json\Server\Server')
            ->setMethods(array('getResponse'))
            ->getMock();

        $mock_server->expects($this->any())
            ->method('getResponse')
            ->with(true);

        $sm = $this->getApplicationServiceLocator()->setAllowOverride(true);
        $sm->setService('json_server', $mock_server);

        $this->dispatch('/api.json-rpc', 'OPTIONS');

        $this->assertControllerName('json_rpc');
        $this->assertActionName('handle');
        $this->assertResponseStatusCode(200);

        $this->assertEquals('', $this->getResponse()
            ->getContent());
    }

    public function testHandleActionGet()
    {
        $mock_server = $this->getMockBuilder('JRpc\Json\Server\Server')
            ->setMethods(array('setReturnResponse', 'initializeClass', 'getServiceMap', 'handle'))
            ->getMock();

        $mock_server->expects($this->once())
            ->method('setReturnResponse')
            ->with(true);

        $mock_server->expects($this->once())
            ->method('initializeClass');

        $mock_server->expects($this->once())
            ->method('getServiceMap')
            ->will($this->returnValue(array('getServiceMap')));

        $mock_server->expects($this->never())
            ->method('handle')
            ->will($this->returnValue(array('handle')));

        $sm = $this->getApplicationServiceLocator()->setAllowOverride(true);
        $sm->setService('json_server', $mock_server);

        $this->dispatch('/api.json-rpc', 'GET');

        $this->assertControllerName('json_rpc');
        $this->assertActionName('handle');
        $this->assertResponseStatusCode(200);

        $this->assertEquals('getServiceMap', current($this->getResponse()
            ->getContent()));
    }
}
