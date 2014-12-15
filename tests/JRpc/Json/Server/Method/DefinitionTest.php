<?php

namespace JRpcTest\Json\Server\Method;

use JRpc\Json\Server\Method\Definition;

class DefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function testNameSm()
    {
        $definition = new Definition();
        $definition->setCallback(array());
        $definition->setNameSm('namesm');

        $this->assertEquals('namesm', $definition->getNameSm());

        $outArray = $definition->toArray();

        $this->assertArrayHasKey('nameSm', $outArray);
        $this->assertEquals('namesm', $outArray['nameSm']);
    }
}
