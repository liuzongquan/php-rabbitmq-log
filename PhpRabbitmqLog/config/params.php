<?php
/**
 * Created by PhpStorm.
 * User: liuzongquan
 * Date: 16/9/19
 * Time: 12:49
 */

return [
    'log' => array([
        'dir' => '/tmp',
        'base_name' => 'api',
        'postfix' => '.Y-m-d',
    ]),
    'rabbitmq' =>array([
        'host' => 'localhost',
        'port' => 15672,
        'user' => 'guest',
        'password' => 'guest',
        'vhost' => '/',
    ]),
];