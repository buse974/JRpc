<?php

return array(
    'modules' => array(
        'JRpc',
        'Zend\Router'
    ),
    'module_listener_options' => array(
        'module_paths' => array(
            'JRpc' => __DIR__.'/../../',
        ),
        'config_glob_paths' => array(
            __DIR__.'/autoload/global.php',
        ),
    ),
);
