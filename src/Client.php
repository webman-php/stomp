<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Webman\Stomp;

use Workerman\Stomp\Client as StompClient;

/**
 * Class Stomp
 * @package support
 *
 * Strings methods
 * @method static void send($queue, $body, array $headers = [])
 */
class Client
{

    /**
     * @var Client[]
     */
    protected static $_connections = null;

    /**
     * @var array
     */
    protected $_queue = [];

    /**
     * @var StompClient
     */
    protected $_client;

    /**
     * Client constructor.
     * @param $host
     * @param array $options
     */
    public function __construct($host, $options = [])
    {
        $this->_client = new StompClient($host, $options);
        $this->_client->onConnect = function ($client) {
            foreach ($this->_queue as $item) {
                $client->{$item[0]}(... $item[1]);
            }
            $this->_queue = [];
        };
        $this->_client->connect();
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if ($this->_client->getState() != StompClient::STATE_ESTABLISHED) {
            if (in_array($name, [
                'subscribe',
                'subscribeWithAck',
                'unsubscribe',
                'send',
                'ack',
                'nack',
                'disconnect'])) {
                $this->_queue[] = [$name, $arguments];
                return null;
            }
        }
        return $this->_client->{$name}(...$arguments);
    }

    /**
     * @param string $name
     * @return Client
     */
    public static function connection($name = 'default') {
        if (!isset(static::$_connections[$name])) {
            $config = config('stomp', config('plugin.webman.stomp.stomp', []));
            if (!isset($config[$name])) {
                throw new \RuntimeException("RedisQueue connection $name not found");
            }
            $host = $config[$name]['host'];
            $options = $config[$name]['options'];
            $client = new static($host, $options);
            static::$_connections[$name] = $client;
        }
        return static::$_connections[$name];
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::connection('default')->{$name}(... $arguments);
    }
}
