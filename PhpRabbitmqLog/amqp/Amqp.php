<?php
/**
 * Created by PhpStorm.
 * User: liuzongquan
 * Date: 16/9/19
 * Time: 11:27
 */

namespace yidu\php\rabbitmq\amqp;


use PhpAmqpLib\Connection\AMQPStreamConnection;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;


/**
 * AMQP wrapper.
 *
 * @property AMQPConnection $connection AMQP connection.
 * @property AMQPChannel $channel AMQP channel.
 * @author Alexey Kuznetsov <mirakuru@webtoucher.ru>
 * @since 2.0
 */
class Amqp
{
    const TYPE_TOPIC = 'topic';
    const TYPE_DIRECT = 'direct';
    const TYPE_HEADERS = 'headers';
    const TYPE_FANOUT = 'fanout';

    /**
     * @var AMQPConnection
     */
    protected static $ampqConnection;

    /**
     * @var AMQPChannel
     */
    protected static $channel;

    /**
     * @var AMQPChannel[]
     */
    protected static $channels = [];

    /**
     * @var string
     */
    public static $host = 'localhost';

    /**
     * @var integer
     */
    public static $port = 5672;

    /**
     * @var string
     */
    public static $user = 'guest';

    /**
     * @var string
     */
    public static $password = 'guest';

    /**
     * @var string
     */
    public static $vhost = '/';

    public static $params;

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        if (empty(self::$user)) {
            throw new Exception("Parameter 'user' was not set for AMQP connection.");
        }
        if (empty(self::$ampqConnection)) {
            self::$params = require __DIR__ . "/../config/params.php";
            self::$host = self::$params['rabbitmq']['host'];
            self::$port = self::$params['rabbitmq']['port'];
            self::$user = self::$params['rabbitmq']['user'];
            self::$password = self::$params['rabbitmq']['password'];
            self::$vhost = self::$params['rabbitmq']['vhost'];
            self::$ampqConnection =new AMQPStreamConnection(
                self::$host,
                self::$port,
                self::$user,
                self::$password,
                self::$vhost
            );
        }
        self::$channel = self::getChannel();
    }

    /**
     * Returns AMQP connection.
     *
     * @return AMQPConnection
     */
    public static function getConnection()
    {
        return self::$ampqConnection;
    }

    /**
     * Returns AMQP connection.
     *
     * @param string $channel_id
     * @return AMQPChannel
     */
    public static function getChannel($channel_id = null)
    {
        $index = $channel_id ?: 'default';
        if (!array_key_exists($index, self::$channels)) {
            self::$channels[$index] = self::$ampqConnection->channel($channel_id);
        }
        return self::$channels[$index];
    }

    /**
     * Sends message to the exchange.
     *
     * @param string $exchange
     * @param string $routing_key
     * @param string|array $message
     * @param string $type Use self::TYPE_DIRECT if it is an answer
     * @return void
     */
    public static function send($exchange, $routing_key, $message, $type = self::TYPE_FANOUT)
    {
        $message = self::prepareMessage($message);
        self::$channel->exchange_declare($exchange, $type, false, true, false);
        self::$channel->basic_publish($message, $exchange, $routing_key);
    }

    /**
     * Sends message to the exchange and waits for answer.
     *
     * @param string $exchange
     * @param string $routing_key
     * @param string|array $message
     * @param integer $timeout Timeout in seconds.
     * @return string
     */
    public static function ask($exchange, $routing_key, $message, $timeout)
    {
        list ($queueName) = self::$channel->queue_declare('', false, false, true, false);
        $message = self::prepareMessage($message, [
            'reply_to' => $queueName,
        ]);
        // queue name must be used for answer's routing key
        self::$channel->queue_bind($queueName, $exchange, $queueName);

        $response = null;
        $callback = function(AMQPMessage $answer) use ($message, &$response) {
            $response = $answer->body;
        };

        self::$channel->basic_consume($queueName, '', false, false, false, false, $callback);
        self::$channel->basic_publish($message, $exchange, $routing_key);
        while (!$response) {
            // exception will be thrown on timeout
            self::$channel->wait(null, false, $timeout);
        }
        return $response;
    }

    /**
     * Listens the exchange for messages.
     *
     * @param string $exchange
     * @param string $routing_key
     * @param callable $callback
     * @param string $type
     */
    public static function listen($exchange, $routing_key, $callback, $type = self::TYPE_TOPIC)
    {
        list ($queueName) = self::$channel->queue_declare();
        if ($type == Amqp::TYPE_DIRECT) {
            self::$channel->exchange_declare($exchange, $type, false, true, false);
        }
        self::$channel->queue_bind($queueName, $exchange, $routing_key);
        self::$channel->basic_consume($queueName, '', false, true, false, false, $callback);

        while (count(self::$channel->callbacks)) {
            self::$channel->wait();
        }

        self::$channel->close();
        self::$connection->close();
    }

    /**
     * Returns prepaired AMQP message.
     *
     * @param string|array|object $message
     * @param array $properties
     * @return AMQPMessage
     * @throws Exception If message is empty.
     */
    public static function prepareMessage($message, $properties = null)
    {
        if (empty($message)) {
            throw new Exception('AMQP message can not be empty');
        }
        if (is_array($message) || is_object($message)) {
            $message = Json::encode($message);
        }
        return new AMQPMessage($message, $properties);
    }
}