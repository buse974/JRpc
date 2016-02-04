<?php

namespace JRpc\Json\Server;

use Zend\Stdlib\ArrayObject;

class ResponseSet extends ArrayObject
{
    public function __toString()
    {
        return '['.implode(',', $this->getArrayCopy()).']';
    }
}
