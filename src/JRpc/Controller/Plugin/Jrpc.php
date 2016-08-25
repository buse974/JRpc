<?php
namespace JRpc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Jrpc extends AbstractPlugin
{

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var \JRpc\Json\Server\Server
     */
    protected $service;

    /**
     * 
     * @param array $settings
     * @param \JRpc\Json\Server\Server $service
     */
    public function __construct($settings,\JRpc\Json\Server\Server $service)
    {
        $this->service = $service;
        $this->settings = $settings;
    }

    /**
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     *
     * @return \JRpc\Json\Server\Server
     */
    public function getServer()
    {
        return $this->service;
    }
}