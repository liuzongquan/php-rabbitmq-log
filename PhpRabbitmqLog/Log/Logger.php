<?php
/**
 * Created by PhpStorm.
 * User: liuzongquan
 * Date: 16/9/19
 * Time: 11:21
 */

namespace yidu\php\rabbitmq\Log;
use yidu\php\rabbitmq\amqp\Amqp;


class Logger
{
    public static $rabbitmq ;
    public static $params;
    public function init(){
        self::$rabbitmq= new Amqp();
        self::$params = require_once(__DIR__ . "../config/params.php");
    }

    const LEVEL_INFO = "info";
    const LEVEL_TRACE = "trace";
    const LEVEL_WARNING = "warning";
    const LEVEL_ERROR = "error";


    /**
     * @param $message
     * @param string $level
     * @param string $category
     */
    public static function log($message, $level, $category = 'api'){
        if($level == Logger::LEVEL_INFO){/* send log message to rabbitmq. */
            $message = self::formatMessage($message,$level,$category);
            self::$rabbitmq->send($category,$category,$message);
        }else{/* write log message to log file. */

        }
    }

    public static function info($message, $category = "api"){
        $category = Logger::LEVEL_INFO.".".$category;
        return self::log($message, Logger::LEVEL_INFO, $category);
    }

    public static function trace($message, $category = "api"){
        $category = Logger::LEVEL_TRACE.".".$category;
        return self::log($message, Logger::LEVEL_TRACE, $category);
    }

    public static function warning($message, $category = "api"){
        $category = Logger::LEVEL_WARNING.".".$category;
        return self::log($message, Logger::LEVEL_WARNING, $category);
    }

    public static function error($message, $category = "api"){
        $category = Logger::LEVEL_ERROR.".".$category;
        return self::log($message, Logger::LEVEL_ERROR, $category);
    }

    /**
     * @param array $array
     * @return string
     */
    private static function iterate_array(array $array){
        $str = "";
        foreach($array as $key=>$val){
            if(is_array($val)){
                $str=$str.self::iterate_array($val);
            }else{
                $str=$str."['".$key."'='".$val."']";
            }
        }
        return $str;
    }

    /**
     * Formats a log message for display as a string.
     * @param array $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return string the formatted message
     */
    public static function formatMessage($text, $level, $category)
    {

        if (!is_string($text)) {
            if ($text instanceof \Throwable || $text instanceof \Exception) {
                $text = (string) $text;
            }
            else{
                if(is_array($text)){
                    $text = iterate_array($text);
                }else{
                    $text = VarDumper::export($text);
                }
            }
        }
        $prefix = getMessagePrefix();
        return $prefix."[$level][$category] $text";
//        . (empty($traces) ? '' : "\n    " . implode("\n    ", $traces));

    }

    public static function getMessagePrefix(){
        return date('Y-m-d H:i:s') . " [-][-][-]";
    }
}