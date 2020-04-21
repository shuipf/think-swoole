<?php
// +----------------------------------------------------------------------
// | InteractsWithPool 连接池接口
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\concerns;

use RuntimeException;
use Swoole\Coroutine\Channel;

trait InteractsWithPool
{
    /**
     * 连接池列表
     * @var Channel[]
     */
    protected $pools = [];

    /**
     * 记录连接池当前连接数
     * @var array
     */
    protected $connectionCount = [];

    /**
     * 获取连接池通道
     * @param $name
     * @return Channel
     */
    protected function getPool($name)
    {
        if (empty($this->pools[$name])) {
            $this->pools[$name] = new Channel($this->getPoolMaxActive($name));
        }
        return $this->pools[$name];
    }

    /**
     * 从连接池中获取一个链接
     * @param $name
     * @return mixed
     */
    protected function getPoolConnection($name)
    {
        $pool = $this->getPool($name);
        if (!isset($this->connectionCount[$name])) {
            $this->connectionCount[$name] = 0;
        }
        //判断连接池中的连接数
        if ($pool->isEmpty() && $this->connectionCount[$name] < $this->getPoolMaxActive($name)) {
            //新建链接
            $connection = $this->createPoolConnection($name);
            $this->connectionCount[$name]++;
        } else {
            //从连接池取一个链接
            $connection = $pool->pop($this->getPoolMaxWaitTime($name));
            if ($connection === false) {
                throw new RuntimeException(
                    sprintf(
                        "%s 获取连接超时 %.2f(s), 当前连接池连接数: %d, 全部连接数: %d",
                        $name,
                        $this->getPoolMaxWaitTime($name),
                        $pool->length(),
                        $this->connectionCount[$name] ?? 0
                    )
                );
            }
        }
        return $this->wrapProxy($pool, $connection);
    }

    /**
     * 代理连接，并增加资源回收
     * @param Channel $pool
     * @param $connection
     * @return mixed
     */
    protected function wrapProxy(Channel $pool, $connection)
    {
        //手册说明 https://wiki.swoole.com/wiki/page/1015.html
        defer(
            function () use ($pool, $connection) {
                //自动归还
                if (!$pool->isFull()) {
                    $pool->push($connection, 0.001);
                }
            }
        );
        return $connection;
    }

    /**
     * 创建连接池需要的链接对象
     * @param string $name
     * @return mixed
     */
    abstract protected function createPoolConnection(string $name);

    /**
     * 连接池最大活动连接数
     * @param $name
     * @return int
     */
    abstract protected function getPoolMaxActive($name): int;

    /**
     * 最大等待时间
     * @param $name
     * @return int
     */
    abstract protected function getPoolMaxWaitTime($name): int;

}