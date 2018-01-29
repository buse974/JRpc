<?php
namespace JRpc\Json\Server;

use Zend\Json\Server\Server as BaseServer;
use Zend\Json\Server\Error as RPCERROR;
use JRpc\Json\Server\Exception\AbstractException;
use JRpc\Json\Server\Exception\ParseErrorException;
use Zend\Server\Reflection;
use Zend\Server\Method\Prototype;
use Zend\Server\Method\Parameter;
use Zend\Code\Reflection\DocBlockReflection;
use Zend\Json;
use Zend\Json\Server\Request;
use Interop\Container\ContainerInterface;
use Zend\Json\Server\Exception;

class Server extends BaseServer
{

    /**
     *
     * @var \Zend\Cache\Storage\StorageInterface |null
     */
    protected $cache;

    /**
     *
     * @var bool|null
     */
    protected $persistence = null;

    /**
     *
     * @var \Interop\Container\ContainerInterface
     */
    protected $container;

    /**
     *
     * @var array
     */
    protected $options;

    /**
     *
     * @param ContainerInterface $container            
     */
    public function __construct(ContainerInterface $container, $options)
    {
        parent::__construct();
        
        $this->container = $container;
        $this->options = $options;
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Zend\Json\Server\Server::_handle()
     */
    protected function handleRequest()
    {
        try {
            $request = $this->getRequest();
            if ($request->isParseError() === true) {
                throw new ParseErrorException();
            }
            if (($ret = $this->getParentHandle()) instanceof RPCERROR && ($ret = $ret->getData()) instanceof \Exception) {
                throw $ret;
            }
        } catch (AbstractException $e) {
            if(isset($this->options['log'])) {
                $this->container->get($this->options['log'])->err('(' . $e->getCode() . ') ' . $e->getMessage());
            }
            return $this->fault($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            if(isset($this->options['log'])) {
                $this->container->get($this->options['log'])->err('(' . $e->getCode() . ') ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine(), $e->getTrace());
            }
            return ((isset($this->options['environment']) && $this->options['environment'] === "dev")) ? $this->fault('(' . $e->getCode() . ') ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine(), $e->getCode(), $e->getTrace()) : $this->fault('Internal error', RPCERROR::ERROR_INTERNAL);
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
        return parent::handleRequest();
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Zend\Server\AbstractServer::_dispatch()
     */
    public function _dispatch(\Zend\Server\Method\Definition $invocable, array $params)
    {
        return call_user_func_array([$this->container->get($invocable->getNameSm()),$invocable->getCallback()->getMethod()], $params);
    }
    
    /**
     * 
     * @param \Zend\Server\Method\Definition $invocable
     * @param array $params
     * @return mixed
     */
    public function dispatch(\Zend\Server\Method\Definition $invocable, array $params)
    {
        return $this->_dispatch($invocable, $params);
    }

    /**
     * Initialize all class.
     */
    public function initializeClass()
    {
        if (!isset($this->options['services']) || !is_array($this->options['services'])) {
            return;
        }
        
        if ($this->getPersistence() && $this->getCache() !== null && ($definition = $this->getCache()->getItem('jrpc-definition')) !== null && ($serviceMap = $this->getCache()->getItem('jrpc-serviceMap')) !== null) {
            $this->table = $definition;
            $this->serviceMap = $serviceMap;
            
            return;
        }
        
        foreach ($this->options['services'] as $c) {
            try {
                $this->setClass($c, ((isset($c['namespace'])) ? $c['namespace'] : ''));
            } catch (\Exception $e) {
                //$this->container->get($this->options['log'])->err($e->getMessage() . ': '. $c);
                //print_r($c);
                //print_r("\n <br>");
            }
        }
        
        if ($this->getPersistence() && $this->getCache() !== null) {
            $this->getCache()->setItem('jrpc-definition', $this->table);
            $this->getCache()->setItem('jrpc-serviceMap', $this->serviceMap);
        }
    }

    /**
     * 
     * @return string|\JRpc\Json\Server\ResponseSet|NULL|\Zend\Json\Server\Response
     */
    public function multiHandle()
    {
        $input = $this->readInput();
        $post = Json\Json::decode($input, Json\Json::TYPE_ARRAY);
        
        if ($input[0] === '[') {
            $content = new ResponseSet();
            foreach ($post as $p) {
                $this->request = null;
                $this->response = null;
                $request = new Request();
                $request->setOptions($p);
                $request->setVersion(self::VERSION_2);
                $this->setRequest($request);
                
                $content->append($this->handle());
            }
        } else {
            $request = new Request();
            $request->setOptions($post);
            $request->setVersion(self::VERSION_2);
            $this->setRequest($request);

            $content = $this->handle();
        }
        
        return $content;
    }

    /**
     * 
     * @return string
     */
    public function readInput()
    {
        return file_get_contents('php://input');
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
        if ($this->container->has($class)) {
            $obj = $this->container->get($class);
        }
        
        $reflection = Reflection::reflectClass($obj, $argv, $namespace);
        
        foreach ($reflection->getMethods() as $method) {
            $docComment = $method->getDocComment();
            if (($docComment !== false && (new DocBlockReflection($docComment))->hasTag('invokable')) || in_array($method->getName(), $methods)) {
                $definition = $this->_buildSignature($method, $class);
                $this->addMethodServiceMap($definition);
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
        $method = empty($ns) ? strtolower($shortName) . '.' . $name : $ns . '.' . $name;
        
        // Ignore Because copy to parent::_buildSignature
        // @codeCoverageIgnoreStart
        if (! $this->overwriteExistingMethods && $this->table->hasMethod($method)) {
            throw new Exception\RuntimeException('Duplicate method registered: ' . $method);
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
                $param = new Parameter(array('type' => $this->_fixType($parameter->getType()),'name' => $parameter->getName(),'optional' => $parameter->isOptional()));
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
        } elseif ($this->container->has($class)) {
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
            $this->persistence = (isset($this->options['persistence']) && $this->options['persistence'] == true);
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
            if (isset($this->options['cache']) && is_string($this->options['cache'])) {
                $this->cache = $this->container->get($this->options['cache']);
            }
        }
        
        return $this->cache;
    }

}
