<?php
// +----------------------------------------------------------------------
// | Client
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\pool;

use Smf\ConnectionPool\Connectors\ConnectorInterface;
use think\helper\Arr;

class Client implements ConnectorInterface
{
    /**
     * 连接到指定的服务器并返回连接资源
     * @param array $config
     * @return \Swoole\Coroutine\Client
     */
    public function connect(array $config)
    {
        $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        $host = Arr::pull($config, 'host');
        $port = Arr::pull($config, 'port');
        $timeout = Arr::pull($config, 'timeout');
        $client->set($config);
        $client->connect($host, $port, $timeout);
        return $client;
    }

    /**
     * 断开连接并释放资源
     * @param \Swoole\Coroutine\Client $connection
     * @return mixed
     */
    public function disconnect($connection)
    {
        $connection->close();
    }

    /**
     * 是否建立连接
     * @param \Swoole\Coroutine\Client $connection
     * @return bool
     */
    public function isConnected($connection): bool
    {
        return $connection->isConnected();
    }

    /**
     * 重置连接
     * @param \Swoole\Coroutine\Client $connection
     * @param array $config
     * @return mixed
     */
    public function reset($connection, array $config)
    {
    }

    /**
     * 验证连接
     * @param \Swoole\Coroutine\Client $connection
     * @return bool
     */
    public function validate($connection): bool
    {
        return $connection instanceof \Swoole\Coroutine\Client;
    }
}