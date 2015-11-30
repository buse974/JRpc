<?php

return array(
    'modules' => array(
        'JRpc',
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
