<?php

namespace JsonRpcServer\Json\Server\Method;

use Zend\Server\Method\Definition as BaseDefinition;

class Definition extends BaseDefinition
{
    /**
     * @var null|string
     */
    protected $nameSm;

    /**
     * Set stirng sm
     * 
     * @param string $nameSm
     * 
     * @return \JsonRpcServer\Json\Server\Method\Definition
     */
    public function setNameSm($nameSm)
    {
        $this->nameSm = $nameSm;
        
        return $this;
    }
     
    /**
     * Get sm string
     * 
     * @return null|string
     */
    public function getNameSm()
    {
        return $this->nameSm;
    }
    
    /**
     * Serialize to array
     *
     * @return array
     */
    public function toArray()
    {
    	return parent::toArray() + array('nameSm' => $this->getNameSm());
    }
}
