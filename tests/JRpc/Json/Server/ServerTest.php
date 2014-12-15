<?php

namespace JRpcTest\Json\Server;

use JRpc\Json\Server\Server;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Zend\Server\Reflection\ReflectionMethod;
use Zend\Server\Reflection\ReflectionClass;
use Mock\Mock;
use JRpc\Json\Server\Method\Definition;
use Zend\Server\Method\Callback;

class ServerTest extends AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(
                include __DIR__.'/../../../config/application.config.php'
        );
        parent::setUp();
    }

    public function testHandleWhitAbstractException()
    {
        // Mock request
        $m_request = $this->getMockBuilder('request')
                          ->setMethods(array('isParseError', 'getMethod'))
                          ->getMock();

        $m_request->expects($this->once())
                  ->method('isParseError')
                  ->will($this->returnValue(false));

        $m_request->expects($this->once())
                  ->method('getMethod')
                  ->will($this->returnValue('un_methode'));

        // Mock exception
        $mock_exception = $this->getMockBuilder('JRpc\Json\Server\Exception\AbstractException')
                               ->setConstructorArgs(array('message AbstractException', 2345))
                               ->getMock();
        // Mock Service Locator
        $mock_sm = $this->getMockBuilder('Zend\ServiceManager\ServiceLocatorInterface')
                        ->disableOriginalConstructor()
                        ->setMethods(array('get', 'err', 'has'))
                        ->getMock();

        $mock_sm->expects($this->any())
                ->method('get')
                ->will($this->onConsecutiveCalls(array('json-rpc-server' => array('log' => 'log')), $this->returnSelf()));

        $mock_sm->expects($this->any())
                ->method('err')
                ->with('(2345) message AbstractException');

        // mock data
        $m_data = $this->getMockBuilder('Zend\Json\Server\Error')
                       ->disableOriginalConstructor()
                       ->setMethods(array('getData'))
                       ->getMock();

        $m_data->expects($this->once())
               ->method('getData')
               ->will($this->returnValue($mock_exception));

        /////////////////////
        $server = $this->getMockBuilder('JRpc\Json\Server\Server')
                       ->setMethods(array('getParentHandle', 'fault', 'getEventManager', 'trigger', 'getRequest'))
                       ->disableOriginalConstructor()
                       ->getMock();

        $server->setServiceLocator($mock_sm);

        $server->expects($this->once())
               ->method('getRequest')
               ->will($this->returnValue($m_request));

        $server->expects($this->once())
               ->method('getEventManager')
               ->will($this->returnSelf());

        $server->expects($this->once())
               ->method('trigger')
               ->with('sendRequest.pre', null, array('methode' => 'un_methode'));

        $server->expects($this->once())
               ->method('getParentHandle')
               ->will($this->returnValue($m_data));

        $server->expects($this->once())
               ->method('fault')
                ->with('message AbstractException', 2345);

        $ref_server = new \ReflectionClass('JRpc\Json\Server\Server');
        $ref_handle = $ref_server->getMethod('_handle');
        $ref_handle->setAccessible(true);
        $ref_handle->invoke($server);
    }

    public function testHandle()
    {
        // Mock request
        $m_request = $this->getMockBuilder('request')
                          ->setMethods(array('isParseError', 'getMethod'))
                          ->getMock();

        $m_request->expects($this->once())
                  ->method('isParseError')
                  ->will($this->returnValue(false));

        $m_request->expects($this->once())
                  ->method('getMethod')
                  ->will($this->returnValue('un_methode'));

        // Mock Service Locator
        $mock_sm = $this->getMockBuilder('Zend\ServiceManager\ServiceLocatorInterface')
                        ->disableOriginalConstructor()
                        ->setMethods(array('get', 'err', 'has'))
                        ->getMock();

        $mock_sm->expects($this->any())
                ->method('get')
                ->will($this->onConsecutiveCalls(array('json-rpc-server' => array('log' => 'log')), $this->returnSelf()));

        $mock_sm->expects($this->any())
                ->method('err')
                ->with('(1234) mock_exception in /home/buse974/Documents/buse974/studnet/apiwow/vendor/buse974/jrpc/tests/JRpc/Json/Server/ServerTest.php line 137');

        // mock data
        $m_data = $this->getMockBuilder('Zend\Json\Server\Error')
                       ->disableOriginalConstructor()
                       ->setMethods(array('getData'))
                       ->getMock();

        $m_data->expects($this->once())
               ->method('getData')
               ->will($this->returnValue(new \Exception('mock_exception', 1234)));

        /////////////////////
        $server = $this->getMockBuilder('JRpc\Json\Server\Server')
                       ->setMethods(array('getParentHandle', 'fault', 'getEventManager', 'trigger', 'getRequest'))
                       ->disableOriginalConstructor()
                       ->getMock();

        $server->setServiceLocator($mock_sm);

        $server->expects($this->once())
               ->method('getRequest')
               ->will($this->returnValue($m_request));

        $server->expects($this->once())
               ->method('getEventManager')
               ->will($this->returnSelf());

        $server->expects($this->once())
               ->method('trigger')
               ->with('sendRequest.pre', null, array('methode' => 'un_methode'));

        $server->expects($this->once())
               ->method('getParentHandle')
               ->will($this->returnValue($m_data));

        $server->expects($this->once())
               ->method('fault')
               ->with('Internal error', -32603);

        $ref_server = new \ReflectionClass('JRpc\Json\Server\Server');
        $ref_handle = $ref_server->getMethod('_handle');
        $ref_handle->setAccessible(true);
        $ref_handle->invoke($server);
    }

    public function testDispatch()
    {
        $mock = new Mock();

        $mock_sm = $this->getMockBuilder('Zend\ServiceManager\ServiceLocatorInterface')
                        ->disableOriginalConstructor()
                        ->setMethods(array('get', 'has'))
                        ->getMock();

        $mock_sm->expects($this->any())
                ->method('get')
                ->with('maclasse_sm')
                ->will($this->returnValue($mock));

        $definition = new Definition();
        $definition->setNameSm('maclasse_sm');
        $definition->setCallback((new Callback())->setMethod('uneMethode'));

        $server = new Server();
        $server->setServiceLocator($mock_sm);

        $out = $server->_dispatch($definition, array());

        $this->assertEquals($mock->uneMethode(), $out);
    }

    public function testInitializeClass()
    {
        $server = $this->getMockBuilder('JRpc\Json\Server\Server')
                       ->setMethods(array('getCache', 'setClass'))
                       ->disableOriginalConstructor()
                       ->getMock();

        $server->setServiceLocator($this->getApplicationServiceLocator());

        $m_cache = $this->getMockBuilder('cache')
                        ->setMethods(array('getItem', 'setItem'))
                        ->getMock();

        $m_cache->expects($this->any())
                ->method('getItem')
                ->will($this->returnValue(null));

        $m_cache->expects($this->exactly(2))
                ->method('setItem')
                ->will($this->returnValue(null));

        $server->expects($this->any())
               ->method('getCache')
               ->will($this->returnValue($m_cache));

        $server->expects($this->once())
               ->method('setClass')
               ->with('un_service');

        $server->initializeClass();
    }

    public function testInitializeClassByCache()
    {
        $server = $this->getMockBuilder('JRpc\Json\Server\Server')
                       ->setMethods(array('getCache'))
                       ->disableOriginalConstructor()
                       ->getMock();

        $server->setServiceLocator($this->getApplicationServiceLocator());

        $m_cache = $this->getMockBuilder('cache')
                        ->setMethods(array('getItem'))
                        ->getMock();

        $m_cache->expects($this->exactly(2))
                ->method('getItem')
                ->with($this->callback(function ($param) {
                    return ($param === 'jrpc-definition' || $param === 'jrpc-serviceMap');
                }))
                ->will($this->onConsecutiveCalls('definition', 'serviceMap'));

        $server->expects($this->any())
               ->method('getCache')
               ->will($this->returnValue($m_cache));

        $server->initializeClass();

        $ref_server = new \ReflectionClass('JRpc\Json\Server\Server');
        $ref_table = $ref_server->getProperty('table');
        $ref_table->setAccessible(true);
        $this->assertEquals('definition', $ref_table->getValue($server));

        $ref_serviceMap = $ref_server->getProperty('serviceMap');
        $ref_serviceMap->setAccessible(true);
        $this->assertEquals('serviceMap', $ref_serviceMap->getValue($server));
    }

    public function testSetClass()
    {
        $mock = new Mock();
        $def = new Definition();

        $mock_sm = $this->getMockBuilder('Zend\ServiceManager\ServiceLocatorInterface')
                        ->disableOriginalConstructor()
                        ->setMethods(array('has', 'get'))
                        ->getMock();

        $mock_sm->expects($this->any())
                ->method('has')
                ->with('maclasse_sm')
                ->will($this->returnValue(true));

        $mock_sm->expects($this->any())
                ->method('get')
                ->with('maclasse_sm')
                ->will($this->returnValue($mock));

        $serv = $this->getMockBuilder('JRpc\Json\Server\Server')
                     ->setMethods(array('_addMethodServiceMap', '_buildSignature'))
                     ->disableOriginalConstructor()
                     ->getMock();

        $serv->expects($this->any())
             ->method('_addMethodServiceMap')
             ->with($def)
             ->will($this->returnValue(true));

        $serv->expects($this->once())
             ->method('_buildSignature')
             ->with($this->callback(function ($arg) {
                  return ($arg->getName() === 'uneMethode');
                 }), 'maclasse_sm')
             ->will($this->returnValue($def));

        $serv->setServiceLocator($mock_sm);
        $serv->setClass('maclasse_sm', 'ns');
    }

    public function testBuildSignature()
    {
        $sm = $this->getApplicationServiceLocator();

        $serv = new Server();
        $serv->setServiceLocator($sm);

        $ref = new \ReflectionClass('\JRpc\Json\Server\Server');
        $methode = $ref->getMethod('_buildSignature');
        $methode->setAccessible(true);

        $out = $methode->invoke($serv, new ReflectionMethod(new ReflectionClass(new \ReflectionClass('DateTime')), new \ReflectionMethod('DateTime', 'format')), 'storage');

        $this->assertInstanceOf('JRpc\Json\Server\Method\Definition', $out);

        $this->assertEquals('datetime.format', $out->getName());
        $this->assertEquals('storage', $out->getNameSm());
    }

    public function testGetPersistenceByConfig()
    {
        $sm = $this->getApplicationServiceLocator();

        $server = new Server();
        $server->setServiceLocator($sm);

        $out = $server->getPersistence();

        $this->assertTrue($out);
    }

    public function testGetCacheByConfig()
    {
        $sm = $this->getApplicationServiceLocator();

        $server = new Server();
        $server->setServiceLocator($sm);

        $out = $server->getCache();

        $this->assertEquals('cache', $out);
    }
}
