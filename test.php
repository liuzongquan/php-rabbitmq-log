<?php
/**
 * Created by PhpStorm.
 * User: liuzongquan
 * Date: 16/9/20
 * Time: 15:34
 */
namespace yidu\php\rabbitmq\Log;
use yidu\php\rabbitmq\Log\Logger;
require_once __DIR__ . "/vendor/autoload.php";
$message = array(
  'foo' => 'bar',
    'bar' => 'foo',
);

$category = "hello-exchange3";

Logger::info($message,$category);
Logger::info($message,$category);