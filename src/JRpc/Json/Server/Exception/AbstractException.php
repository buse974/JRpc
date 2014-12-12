<?php

namespace JsonRpcServer\Json\Server\Exception;

use Zend\Json\Server\Exception\ExceptionInterface;
use \Exception;

abstract class AbstractException extends Exception implements ExceptionInterface
{}
