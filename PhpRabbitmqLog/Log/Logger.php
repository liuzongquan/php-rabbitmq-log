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
    public $rabbitmq;
    public $params;

    public function __construct()
    {
        $this->rabbitmq = new Amqp();
        $this->params = require(__DIR__ . "/../config/params.php");
        date_default_timezone_set("Asia/Hong_Kong");
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
    public function log($message, $level, $category = 'api')
    {
        if($level == Logger::LEVEL_INFO){/* send log message to rabbitmq. */
            $message = self::formatMessage($message,$level,$category);
            $this->rabbitmq->send($category, $category, $message);
        }else{/* write log message to log file. */
            $dir = $this->params['log']['dir'];
            if (!self::endsWith($dir, "/")) {
                $dir = $dir . "/";
            }
            $base_name = $this->params['log']['base_name'];
            $postfix = date($this->params['log']['postfix']);
            $log_file = $dir . $base_name . $postfix;
            $file_handler = fopen($log_file, "a");
            $message = self::formatMessage($message, $level, $category);
            fwrite($file_handler, $message . "\n");
            fclose($file_handler);
        }
    }

    public static function info($message, $category = "api")
    {
        return (new Logger())->log($message, Logger::LEVEL_INFO, $category);
    }

    public static function trace($message, $category = "api")
    {
        return (new Logger())->log($message, Logger::LEVEL_TRACE, $category);
    }

    public static function warning($message, $category = "api")
    {
        return (new Logger())->log($message, Logger::LEVEL_WARNING, $category);
    }

    public static function error($message, $category = "api")
    {
        return (new Logger())->log($message, Logger::LEVEL_ERROR, $category);
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
                    $text = self::iterate_array($text);
                }else{
                    $text = VarDumper::export($text);
                }
            }
        }
        $prefix = self::getMessagePrefix();
        return $prefix."[$level][$category] $text";
//        . (empty($traces) ? '' : "\n    " . implode("\n    ", $traces));

    }

    public static function getMessagePrefix(){
        return date('Y-m-d H:i:s') . " [-][-][-]";
    }

    static function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    static function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }
}