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

namespace Webman\Stomp\Process;

use support\bootstrap\Container;
use Workerman\Stomp\Client as StompClient;
use Webman\Stomp\Client;

/**
 * Class StompConsumer
 * @package process
 */
class Consumer
{
    /**
     * @var string
     */
    protected $_consumerDir = '';

    /**
     * StompConsumer constructor.
     * @param string $consumer_dir
     */
    public function __construct($consumer_dir = '')
    {
        $this->_consumerDir = $consumer_dir;
    }

    /**
     * onWorkerStart.
     */
    public function onWorkerStart()
    {
        $dir_iterator = new \RecursiveDirectoryIterator($this->_consumerDir);
        $iterator = new \RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            if (is_dir($file)) {
                continue;
            }
            $fileinfo = new \SplFileInfo($file);
            $ext = $fileinfo->getExtension();
            if ($ext === 'php') {
                $class = str_replace('/', "\\", substr(substr($file, strlen(base_path())), 0, -4));
                if (!is_a($class, 'Webman\Stomp\Consumer', true)) {
                    continue;
                }
                $consumer = Container::get($class);
                $connection_name = $consumer->connection ?? 'default';
                $queue = $consumer->queue;
                $ack   = $consumer->ack ?? 'auto';
                $connection = Client::connection($connection_name);
                $cb = function ($client, $package, $ack) use ($consumer) {
                    \call_user_func([$consumer, 'consume'], $package['body'], $ack, $client);
                };
                $connection->subscribe($queue, $cb, ['ack' => $ack]);
                /*if ($connection->getState() == StompClient::STATE_ESTABLISHED) {
                    $connection->subscribe($queue, $cb, ['ack' => $ack]);
                } else {
                    $connection->onConnect = function (Client $connection) use ($queue, $ack, $cb) {
                        $connection->subscribe($queue, $cb, ['ack' => $ack]);
                    };
                }*/
            }
        }

    }
}