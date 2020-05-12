<?php
// +----------------------------------------------------------------------
// | InteractsWithPool 连接池接口（db、cache）
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\concerns;

use RuntimeException;
use Swoole\Coroutine\Channel;

trait InteractsWithPool
{
    /**
     * 连接池列表，常见的cache、db
     * @var Channel[]
     */
    protected $pools = [];

    /**
     * 记录连接池当前连接数
     * @var array
     */
    protected $connectionCount = [];

    /**
     * 连接创建时间
     * @var array
     */
    protected $connectionCreationTime = [];

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
            $this->connectionCreationTime[$name] = time();
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
        return $this->wrapProxy($pool, $connection, $name);
    }

    /**
     * 移除连接
     * @param $name
     * @param $connection
     * @return bool
     */
    public function removeConnection($name, $connection): bool
    {
        $this->connectionCount[$name]--;
        go(
            function () use ($name, $connection) {
                try {
                    $this->removePoolConnection($name, $connection);
                } catch (\Throwable $e) {
                    //忽略此异常.
                }
            }
        );
        return true;
    }

    /**
     * 代理连接，并增加资源回收
     * @param Channel $pool
     * @param $connection
     * @param $name
     * @return mixed
     */
    protected function wrapProxy(Channel $pool, $connection, $name)
    {
        //手册说明 https://wiki.swoole.com/#/coroutine/coroutine?id=defer
        //用于资源的释放，会在协程关闭之前 (即协程函数执行完毕时) 进行调用，就算抛出了异常，已注册的 defer 也会被执行
        //自动归还
        defer(
            function () use ($pool, $connection, $name) {
                //判断通道是否已满
                if (!$pool->isFull()) {
                    //判断最大使用时间
                    if (time() - $this->connectionCreationTime[$name] < $this->getPoolMaxUseTime($name)) {
                        //向通道添加连接
                        $pool->push($connection, 0.001);
                    } else {
                        //关闭连接
                        $this->removeConnection($name, $connection);
                    }
                } else {
                    //关闭连接
                    $this->removeConnection($name, $connection);
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
     * 移除连接
     * @param string $name
     * @param $connection
     * @return mixed
     */
    abstract protected function removePoolConnection(string $name, $connection);

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

    /**
     * 最大活动时间
     * @param $name
     * @return int
     */
    abstract protected function getPoolMaxUseTime($name): int;

}