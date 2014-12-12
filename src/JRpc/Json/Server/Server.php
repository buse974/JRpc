<?php

namespace JRpc\Json\Server;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Json\Server\Server as BaseServer;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;
use Zend\Json\Server\Error as RPCERROR;
use JRpc\Json\Server\Exception\AbstractException;
use JRpc\Json\Server\Exception\ParseErrorException;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\Server\Reflection;
use Zend\Server\Method\Prototype;
use Zend\Server\Method\Parameter;
use Zend\Code\Reflection\DocBlockReflection;
use Zend\Cache\Storage\StorageInterface;

class Server extends BaseServer implements ServiceLocatorAwareInterface, EventManagerAwareInterface
{
    protected $serviceLocator;
    protected $events;
    protected $cache;
    protected $persistence = null;

    protected function _handle()
    {
        try {
            $request = $this->getRequest();
            if ($request->isParseError()) {
                throw new ParseErrorException();
            }
            $this->getEventManager()->trigger('sendRequest.pre', null, array('methode' => $request->getMethod()));
            if (($ret = parent::_handle()) instanceof RPCERROR && ($ret = $ret->getData()) instanceof \Exception) {
                throw $ret;
            }
        } catch (AbstractException $e) {
            $sm = $this->getServiceLocator();
            $sm->get($sm->get('Config')['json-rpc-server']['log'])->err('('.$e->getCode().') '.$e->getMessage());

            return $this->fault($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            $sm = $this->getServiceLocator();
            $sm->get($sm->get('Config')['json-rpc-server']['log'])->err('('.$e->getCode().') '.$e->getMessage().' in '.$e->getFile().' line '.$e->getLine(), $e->getTrace());

            return $this->fault('Internal error', RPCERROR::ERROR_INTERNAL);
        }
    }

    public function _dispatch(\Zend\Server\Method\Definition $invocable, array $params)
    {
        return call_user_func_array(array($this->getServiceLocator()->get($invocable->getNameSm()), $invocable->getCallback()->getMethod()), $params);
    }

    public function initializeClass()
    {
    	$config = $this->getServiceLocator()->get('config')['json-rpc-server'];
    	
    	if(!isset($config['services']) && !is_array($config['services'])) {
    		return;
    	}
    	
        if ($this->getPersistence() && $this->getCache() !== null
            && ($definition = $this->cache->getItem('jrpc-definition')) !== null
            && ($serviceMap = $this->cache->getItem('jrpc-serviceMap')) !== null
        ) {
            $this->table = $definition;
            $this->serviceMap = $serviceMap;

            return;
        }

        foreach ($config['services'] as $c) {
            $this->setClass($c);
        }

        if ($this->getPersistence() && $this->getCache() !== null) {
            $this->cache->setItem('jrpc-definition', $this->table);
            $this->cache->setItem('jrpc-serviceMap', $this->serviceMap);
        }
    }

    public function setPersistence($mode)
    {
        $this->persistence = $mode;

        return $this;
    }
    
    public function getPersistence()
    {
    	if(null === $this->persistence) {
    		$config = $this->getServiceLocator()->get('config')['json-rpc-server'];
    		$this->persistence = (isset($config['persistence']) && $config['persistence'] == true) ? true:false;
    	}
    	
    	return $this->persistence;
    }
    
    protected function getCache() {
    	 
    	if(null === $this->cache) {
    		$config = $this->getServiceLocator()->get('config')['json-rpc-server'];
    		if(isset($config['cache']) && is_string($config['cache'])) {
    			$this->cache = $this->getServiceLocator()->get($config['cache']);
    		}
    	}
    	 
    	return $this->cache;
    }

    /**
     * Register a class with the server
     *
     * @param  string $class
     * @param  string $namespace Ignored
     * @param  mixed  $argv      Ignored
     * @return Server
     */
    public function setClass($class, $namespace = '', $argv = null)
    {
        if (2 < func_num_args()) {
            $argv = func_get_args();
            $argv = array_slice($argv, 2);
        }

        $obj = $class;
        if ($this->serviceLocator->has($class)) {
            $obj = $this->getServiceLocator()->get($class);
        }

        $reflection = Reflection::reflectClass($obj, $argv, $namespace);

        foreach ($reflection->getMethods() as $method) {
            $docComment = $method->getDocComment();
            if ($docComment !== false) {
                $scanner    = new DocBlockReflection($docComment);
                if ($scanner->hasTag('invokable')) {
                    $definition = $this->_buildSignature($method, $class);
                    $this->_addMethodServiceMap($definition);
                }
            }
        }

        return $this;
    }

    /**
     * Build a method signature
     *
     * @param  Reflection\AbstractFunction $reflection
     * @param  null|string|object          $class
     * @return Method\Definition
     * @throws Exception\RuntimeException  on duplicate entry
     */
    protected function _buildSignature(Reflection\AbstractFunction $reflection, $class = null)
    {
        $ns         = $reflection->getNamespace();
        $name       = $reflection->getName();
        $shortName  = $reflection->getDeclaringClass()->getShortName();
        $method     = empty($ns) ? strtolower($shortName).'.'.$name : $ns.'.'.$name;

        if (!$this->overwriteExistingMethods && $this->table->hasMethod($method)) {
            throw new Exception\RuntimeException('Duplicate method registered: '.$method);
        }

        $definition = new Method\Definition();
        $definition->setName($method)
                   ->setCallback($this->_buildCallback($reflection))
                   ->setMethodHelp($reflection->getDescription())
                   ->setInvokeArguments($reflection->getInvokeArguments());

        foreach ($reflection->getPrototypes() as $proto) {
            $prototype = new Prototype();
            $prototype->setReturnType($this->_fixType($proto->getReturnType()));
            foreach ($proto->getParameters() as $parameter) {
                $param = new Parameter(array(
                        'type'     => $this->_fixType($parameter->getType()),
                        'name'     => $parameter->getName(),
                        'optional' => $parameter->isOptional(),
                ));
                if ($parameter->isDefaultValueAvailable()) {
                    $param->setDefaultValue($parameter->getDefaultValue());
                }
                $prototype->addParameter($param);
            }
            $definition->addPrototype($prototype);
        }
        if (is_object($class)) {
            $definition->setObject($class);
        } elseif ($this->getServiceLocator()->has($class)) {
            $definition->setNameSm($class);
        }

        $this->table->addMethod($definition);

        return $definition;
    }

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        return $this;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Inject an EventManager instance
     *
     * @param  EventManagerInterface $eventManager
     * @return void
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(array(
                __CLASS__,
                get_called_class(),
        ));
        $this->events = $events;

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager(new EventManager());
        }

        return $this->events;
    }
}
