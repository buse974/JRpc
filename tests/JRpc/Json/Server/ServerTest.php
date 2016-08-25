<?php

namespace JRpcTest\Json\Server;

use JRpc\Json\Server\Method\Definition;
use JRpc\Json\Server\Server;
use Zend\Server\Method\Callback;
use Zend\Server\Reflection\ReflectionClass;
use Zend\Server\Reflection\ReflectionMethod;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ServerTest extends AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(include __DIR__.'/../../../config/application.config.php');
        parent::setUp();
    }

    /**
     * @TODO has cheched
     */
    public function testHandleParseError()
    {
        
        // Mock Service Locator
        $mock_sm = $this->getMockBuilder('Interop\Container\ContainerInterface')
            ->disableOriginalConstructor()
            ->setMethods(array('get', 'err', 'has'))
            ->getMock();

        $mock_sm->expects($this->any())
            ->method('get')
            ->will($this->onConsecutiveCalls(array('json-rpc-server' => array('log' => 'log')), $this->returnSelf()));

        // Mock request
        $m_request = $this->getMockBuilder('Zend\Http\Request')
            ->setMethods(array('isParseError'))
            ->getMock();

        $m_request->expects($this->once())
            ->method('isParseError')
            ->will($this->returnValue(true));

        $server = $this->getMockBuilder('JRpc\Json\Server\Server')
            ->setMethods(['getParentHandle', 'fault', 'getEventManager', 'trigger', 'getRequest'])
            ->setConstructorArgs([$mock_sm,[]])
            ->getMock();

        $server->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($m_request));

        $ref_server = new \ReflectionClass('JRpc\Json\Server\Server');
        $ref_handle = $ref_server->getMethod('_handle');
        $ref_handle->setAccessible(true);
        $res = $ref_handle->invoke($server);
    }

    public function testHandleWhitoutException()
    {
        // Mock request
        $m_request = $this->getMockBuilder('Zend\Http\Request')
            ->setMethods(array('isParseError', 'getMethod'))
            ->getMock();

        $m_request->expects($this->once())
            ->method('isParseError')
            ->will($this->returnValue(false));

        $m_request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('un_methode'));

        // mock data
        $m_data = $this->getMockBuilder('Zend\Json\Server\Error')
            ->disableOriginalConstructor()
            ->setMethods(array('getData'))
            ->getMock();

        $m_data->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('return'));

        // ///////////////////
        $server = $this->getMockBuilder('JRpc\Json\Server\Server')
            ->setMethods(array('getParentHandle', 'fault', 'getEventManager', 'trigger', 'getRequest'))
            ->disableOriginalConstructor()
            ->getMock();

        $server->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($m_request));

        $server->expects($this->once())
            ->method('getEventManager')
            ->will($this->returnSelf());

        $server->expects($this->once())
            ->method('trigger')
            ->with('sendRequest.pre', $server, array('methode' => 'un_methode'));

        $server->expects($this->once())
            ->method('getParentHandle')
            ->will($this->returnValue($m_data));

        $ref_server = new \ReflectionClass('JRpc\Json\Server\Server');
        $ref_handle = $ref_server->getMethod('_handle');
        $ref_handle->setAccessible(true);
        $ref_handle->invoke($server);
    }

    public function testHandleWhitAbstractException()
    {
        // Mock request
        $m_request = $this->getMockBuilder('Zend\Http\Request')
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
        $mock_sm = $this->getMockBuilder('Interop\Container\ContainerInterface')
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

        // ///////////////////
        $server = $this->getMockBuilder('JRpc\Json\Server\Server')
            ->setMethods(array('getParentHandle', 'fault', 'getEventManager', 'trigger', 'getRequest'))
            ->setConstructorArgs([$mock_sm, []])
            ->getMock();

        $server->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($m_request));

        $server->expects($this->once())
            ->method('getEventManager')
            ->will($this->returnSelf());

        $server->expects($this->once())
            ->method('trigger')
            ->with('sendRequest.pre', $server, array('methode' => 'un_methode'));

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
        $m_request = $this->getMockBuilder('Zend\Http\Request')
            ->setMethods(array('isParseError', 'getMethod'))
            ->getMock();

        $m_request->expects($this->once())
            ->method('isParseError')
            ->will($this->returnValue(false));

        $m_request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('un_methode'));

        // Mock Service Locator
        $mock_sm = $this->getMockBuilder('Interop\Container\ContainerInterface')
            ->disableOriginalConstructor()
            ->setMethods(array('get', 'err', 'has'))
            ->getMock();

        $mock_sm->expects($this->any())
            ->method('get')
            ->will($this->onConsecutiveCalls(array('json-rpc-server' => array('log' => 'log')), $this->returnSelf()));

        $mock_sm->expects($this->any())
            ->method('err')
            ->with($this->callback(function ($out) {
            return strpos($out, '(1234) mock_exception') === 0;
        }));

        // mock data
        $m_data = $this->getMockBuilder('Zend\Json\Server\Error')
            ->disableOriginalConstructor()
            ->setMethods(array('getData'))
            ->getMock();

        $m_data->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(new \Exception('mock_exception', 1234)));

        // ///////////////////
        $server = $this->getMockBuilder('JRpc\Json\Server\Server')
            ->setMethods(array('getParentHandle', 'fault', 'getEventManager', 'trigger', 'getRequest'))
            ->setConstructorArgs([$mock_sm, []])
            ->getMock();

        $server->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($m_request));

        $server->expects($this->once())
            ->method('getEventManager')
            ->will($this->returnSelf());

        $server->expects($this->once())
            ->method('trigger')
            ->with('sendRequest.pre', $server, array('methode' => 'un_methode'));

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
        $mock = $this->getMockBuilder('stdClass')->setMethods(['uneMethode'])->getMock();

        $mock_sm = $this->getMockBuilder('Interop\Container\ContainerInterface')
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

        $server = new Server($mock_sm, []);

        $out = $server->_dispatch($definition, array());

        $this->assertEquals($mock->uneMethode(), $out);
    }

    public function testInitializeClass()
    {
        $server = $this->getMockBuilder('JRpc\Json\Server\Server')
            ->setMethods(array('getCache', 'setClass', 'getPersistence'))
            ->setConstructorArgs([$this->getApplicationServiceLocator(), ['services' => ['un_service' => 'un_service']]])
            ->getMock();

            
        $m_cache = $this->getMockBuilder('stdClass')
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
        
        $server->expects($this->any())
            ->method('getPersistence')
            ->will($this->returnValue(true));

        $server->expects($this->once())
            ->method('setClass')
            ->with('un_service');
        
        $server->initializeClass();
    }

    public function testInitializeClassByCache()
    {
        $container = $this->getApplicationServiceLocator();

        $server = $this->getMockBuilder('JRpc\Json\Server\Server')
            ->setMethods(array('getCache'))
            ->setConstructorArgs([$container,$container->get('Config')['json-rpc-server']])
            ->getMock();

        $m_cache = $this->getMockBuilder('stdClass')
            ->setMethods(array('getItem'))
            ->getMock();

        $m_cache->expects($this->exactly(2))
            ->method('getItem')
            ->with($this->callback(function ($param) {
            return $param === 'jrpc-definition' || $param === 'jrpc-serviceMap';
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
        $mock = $this->getMockBuilder('stdClass')->setMethods(['uneMethode']); 
        $def = new Definition();

        $mock_sm = $this->getMockBuilder('Interop\Container\ContainerInterface')
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
            ->setConstructorArgs([$mock_sm,[]])
            ->getMock();

        $serv->expects($this->any())
            ->method('_addMethodServiceMap')
            ->with($def)
            ->will($this->returnValue(true));

        $serv->expects($this->any())
            ->method('_buildSignature')
            ->with($this->callback(function ($arg) {
            return $arg->getName() === 'uneMethode';
        }), 'maclasse_sm')
            ->will($this->returnValue($def));

        $serv->setClass('maclasse_sm', 'ns', array());
    }

    public function testSetClassArrayClass()
    {
        $mock = $this->getMockBuilder('stdClass')->setMethods(['uneMethode']);
        $def = new Definition();

        $mock_sm = $this->getMockBuilder('Interop\Container\ContainerInterface')
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
            ->setConstructorArgs([$mock_sm,[]])
            ->getMock();

        $serv->expects($this->any())
            ->method('_addMethodServiceMap')
            ->with($def)
            ->will($this->returnValue(true));

        $serv->expects($this->any())
            ->method('_buildSignature')
            ->with($this->callback(function ($arg) {
            return $arg->getName() === 'uneMethode';
        }), 'maclasse_sm')
            ->will($this->returnValue($def));

        $serv->setClass(['methods' => ['uneMethode'], 'class' => 'maclasse_sm'], 'ns', array());
    }

    public function testSetPersistence()
    {
        $server = new Server($this->getApplicationServiceLocator(), []);
        $server->setPersistence(true);

        $ref_server = new \ReflectionClass('JRpc\Json\Server\Server');
        $ref_persistence = $ref_server->getProperty('persistence');
        $ref_persistence->setAccessible(true);

        $this->assertEquals(true, $ref_persistence->getValue($server));
    }

    public function testBuildSignature()
    {
        $serv = new Server($this->getApplicationServiceLocator(), []);

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
        $container = $this->getApplicationServiceLocator();
        $server = new Server($container, $container->get('Config')['json-rpc-server']);

        $out = $server->getPersistence();

        $this->assertTrue($out);
    }

    public function testGetCacheByConfig()
    {
        $container = $this->getApplicationServiceLocator();
        $server = new Server($container, $container->get('Config')['json-rpc-server']);

        $out = $server->getCache();

        $this->assertEquals('cache', $out);
    }

    public function testSetEventManager()
    {
        $m_EventManagerInterface = $this->getMockBuilder('Zend\EventManager\EventManagerInterface')
            ->setMethods(array())
            ->getMock();

        $m_EventManagerInterface->expects($this->once())
            ->method('setIdentifiers')
            ->with(array('JRpc\Json\Server\Server', 'JRpc\Json\Server\Server'))
            ->will($this->returnSelf());

        $server = new Server($this->getApplicationServiceLocator(), []);

        $ref_server = new \ReflectionClass('JRpc\Json\Server\Server');
        $ref_events = $ref_server->getProperty('events');
        $ref_events->setAccessible(true);

        $this->assertInstanceOf('JRpc\Json\Server\Server', $server->setEventManager($m_EventManagerInterface));
        $this->assertEquals($m_EventManagerInterface, $ref_events->getValue($server));
    }

    public function testGetEventManagerByDefault()
    {
        
        $server = new Server($this->getApplicationServiceLocator(), []);

        $ref_server = new \ReflectionClass('JRpc\Json\Server\Server');
        $ref_events = $ref_server->getProperty('events');
        $ref_events->setAccessible(true);

        $out = $server->getEventManager();

        $this->assertInstanceOf('Zend\EventManager\EventManagerInterface', $out);
        $this->assertEquals($out, $ref_events->getValue($server));
    }
}
