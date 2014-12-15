<?php
/**
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 */

return array(
    'service_manager' =>  array(
        'services' => array(
            'storage' => 'cache',
        ),
    ),
    'json-rpc-server' => array(
        'cache' => 'storage',
        'log' => 'log-system',
        'persistence' => true,
        'services' => array(
            'un_service',
        ),
    ),
);
