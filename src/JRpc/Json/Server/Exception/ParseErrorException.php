<?php

namespace JsonRpcServer\Json\Server\Exception;

use JsonRpcServer\Json\Server\Error;

class ParseErrorException extends AbstractException
{
    protected $code = Error::ERROR_PARSE;

    protected $message = 'Parse error';
}
