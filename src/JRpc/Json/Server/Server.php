<?php

namespace JRpc\Json\Server;

use Zend\Json\Server\Server as BaseServer;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\EventManager\EventManager;
use Zend\Json\Server\Error as RPCERROR;
use JRpc\Json\Server\Exception\AbstractException;
use JRpc\Json\Server\Exception\ParseErrorException;
use Zend\Server\Reflection;
use Zend\Server\Method\Prototype;
use Zend\Server\Method\Parameter;
use Zend\Code\Reflection\DocBlockReflection;

class Server extends BaseServer implements ServiceLocatorAwareInterface, EventManagerAwareInterface
{
    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var \Zend\EventManager\EventManagerInterface
     */
    protected $events;

    /**
     * @var \Zend\Cache\Storage\StorageInterface|null
     */
    protected $cache;

    /**
     * @var bool|null
     */
    protected $persistence = null;

    /**
     * (non-PHPdoc).
     *
     * @see \Zend\Json\Server\Server::_handle()
     */
    protected function _handle()
    {
        try {
            $request = $this->getRequest();
            if ($request->isParseError() === true) {
                throw new ParseErrorException();
            }
            $this->getEventManager()->trigger('sendRequest.pre', $this, array('methode' => $request->getMethod()));
            if (($ret = $this->getParentHandle()) instanceof RPCERROR && ($ret = $ret->getData()) instanceof \Exception) {
                throw $ret;
            }
        } catch (AbstractException $e) {
            $sm = $this->getServiceLocator();
            $sm->get($sm->get('Config')['json-rpc-server']['log'])
                ->err('('.$e->getCode().') '.$e->getMessage());

            return $this->fault($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            $sm = $this->getServiceLocator();
            $sm->get($sm->get('Config')['json-rpc-server']['log'])
                ->err('('.$e->getCode().') '.$e->getMessage().' in '.$e->getFile().' line '.$e->getLine(), $e->getTrace());

            return $this->fault('Internal error', RPCERROR::ERROR_INTERNAL, $e->getTrace());
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @codeCoverageIgnore
     *
     * @see \Zend\Json\Server\Server::_handle()
     */
    protected function getParentHandle()
    {
        return parent::_handle();
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Zend\Server\AbstractServer::_dispatch()
     */
    public function _dispatch(\Zend\Server\Method\Definition $invocable, array $params)
    {
        return call_user_func_array(array($this->getServiceLocator()->get($invocable->getNameSm()), $invocable->getCallback()->getMethod()), $params);
    }

    /**
     * Initialize all class.
     */
    public function initializeClass()
    {
        $config = $this->getServiceLocator()->get('config')['json-rpc-server'];

        if (!isset($config['services']) && !is_array($config['services'])) {
            return;
        }

        if ($this->getPersistence() && $this->getCache() !== null && ($definition = $this->getCache()->getItem('jrpc-definition')) !== null && ($serviceMap = $this->getCache()->getItem('jrpc-serviceMap')) !== null) {
            $this->table = $definition;
            $this->serviceMap = $serviceMap;

            return;
        }

        foreach ($config['services'] as $c) {
            $this->setClass($c, ((isset($c['namespace'])) ? $c['namespace'] : ''));
        }

        if ($this->getPersistence() && $this->getCache() !== null) {
            $this->getCache()->setItem('jrpc-definition', $this->table);
            $this->getCache()->setItem('jrpc-serviceMap', $this->serviceMap);
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Zend\Json\Server\Server::setClass()
     */
    public function setClass($class, $namespace = '', $argv = null)
    {
        if (2 < func_num_args()) {
            $argv = func_get_args();
            $argv = array_slice($argv, 2);
        }

        $methods = [];
        if (is_array($class)) {
            $methods = $class['methods'];
            $class = $class['class'];
        }

        $obj = $class;
        if ($this->serviceLocator->has($class)) {
            $obj = $this->getServiceLocator()->get($class);
        }

        $reflection = Reflection::reflectClass($obj, $argv, $namespace);

        foreach ($reflection->getMethods() as $method) {
            $docComment = $method->getDocComment();
            if (($docComment !== false && (new DocBlockReflection($docComment))->hasTag('invokable')) || in_array($method->getName(), $methods)) {
                $definition = $this->_buildSignature($method, $class);
                $this->_addMethodServiceMap($definition);
            }
        }

        return $this;
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Zend\Server\AbstractServer::_buildSignature()
     */
    protected function _buildSignature(Reflection\AbstractFunction $reflection, $class = null)
    {
        $ns = $reflection->getNamespace();
        $name = $reflection->getName();
        $shortName = $reflection->getDeclaringClass()->getShortName();
        $method = empty($ns) ? strtolower($shortName).'.'.$name : $ns.'.'.$name;

        // Ignore Because copy to parent::_buildSignature
        // @codeCoverageIgnoreStart
        if (!$this->overwriteExistingMethods && $this->table->hasMethod($method)) {
            throw new Exception\RuntimeException('Duplicate method registered: '.$method);
        }
        // @codeCoverageIgnoreEnd
        $definition = new Method\Definition();
        $definition->setName($method)
            ->setCallback($this->_buildCallback($reflection))
            ->setMethodHelp($reflection->getDescription())
            ->setInvokeArguments($reflection->getInvokeArguments());

        foreach ($reflection->getPrototypes() as $proto) {
            $prototype = new Prototype();
            $prototype->setReturnType($this->_fixType($proto->getReturnType()));
            foreach ($proto->getParameters() as $parameter) {
                $param = new Parameter(array('type' => $this->_fixType($parameter->getType()), 'name' => $parameter->getName(), 'optional' => $parameter->isOptional()));
                // Ignore Because copy to parent::_buildSignature
                // @codeCoverageIgnoreStart
                if ($parameter->isDefaultValueAvailable()) {
                    $param->setDefaultValue($parameter->getDefaultValue());
                }
                // @codeCoverageIgnoreEnd
                $prototype->addParameter($param);
            }
            $definition->addPrototype($prototype);
        }
        if (is_object($class)) {
            // Ignore Because copy to parent::_buildSignature
            // @codeCoverageIgnoreStart
            $definition->setObject($class);
            // @codeCoverageIgnoreEnd
        } elseif ($this->getServiceLocator()->has($class)) {
            $definition->setNameSm($class);
        }

        $this->table->addMethod($definition);

        return $definition;
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Zend\Json\Server\Server::setPersistence()
     */
    public function setPersistence($mode)
    {
        $this->persistence = $mode;

        return $this;
    }

    /**
     * Check persistance.
     *
     * @return bool
     */
    public function getPersistence()
    {
        if (null === $this->persistence) {
            $config = $this->getServiceLocator()->get('config')['json-rpc-server'];
            $this->persistence = (isset($config['persistence']) && $config['persistence'] == true) ? true : false;
        }

        return $this->persistence;
    }

    /**
     * Get Storage if define in config.
     *
     * @return \Zend\Cache\Storage\StorageInterface|null
     */
    public function getCache()
    {
        if (null === $this->cache) {
            $config = $this->getServiceLocator()->get('config')['json-rpc-server'];
            if (isset($config['cache']) && is_string($config['cache'])) {
                $this->cache = $this->getServiceLocator()->get($config['cache']);
            }
        }

        return $this->cache;
    }

    /**
     * Set service locator.
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        return $this;
    }

    /**
     * Get service locator.
     *
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Inject an EventManager instance.
     *
     * @param \Zend\EventManager\EventManagerInterface $eventManager
     */
    public function setEventManager(\Zend\EventManager\EventManagerInterface $events)
    {
        $events->setIdentifiers(array(__CLASS__, get_called_class()));
        $this->events = $events;

        return $this;
    }

    /**
     * (non-PHPdoc).
     *
     * @return \Zend\EventManager\EventManagerInterface
     */
    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager(new EventManager());
        }

        return $this->events;
    }
}
