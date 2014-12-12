<?php

namespace JRpc\Json\Server\Exception;

use JRpc\Json\Server\Error;

class ParseErrorException extends AbstractException
{
    protected $code = Error::ERROR_PARSE;

    protected $message = 'Parse error';
}
