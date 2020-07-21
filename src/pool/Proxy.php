<?php
// +----------------------------------------------------------------------
// | Proxy
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\pool;

use Closure;
use RuntimeException;
use Smf\ConnectionPool\ConnectionPool;
use Smf\ConnectionPool\Connectors\ConnectorInterface;
use Swoole\Coroutine;
use Swoole\Event;
use think\swoole\coroutine\Context;
use think\swoole\Pool;

abstract class Proxy
{
    /**
     * 释放标记名称
     */
    const KEY_RELEASED = '__released';

    /**
     * @var ConnectionPool
     */
    protected $pool;

    /**
     * Proxy constructor.
     * @param Closure $creator 连接对象
     * @param array $config 连接池配置
     */
    public function __construct($creator, $config)
    {
        $this->pool = new ConnectionPool(
            Pool::pullPoolConfig($config),
            new class($creator) implements ConnectorInterface {

                /**
                 * 连接对象
                 * @var
                 */
                protected $creator;

                /**
                 * constructor.
                 * @param $creator
                 */
                public function __construct($creator)
                {
                    $this->creator = $creator;
                }

                /**
                 * 连接到指定的服务器并返回连接资源
                 * @param array $config
                 * @return mixed
                 */
                public function connect(array $config)
                {
                    return call_user_func($this->creator);
                }

                /**
                 * 断开连接并释放资源
                 * @param $connection
                 */
                public function disconnect($connection)
                {
                    //在下一个事件循环开始时执行函数
                    Event::defer(
                        function () {
                            //强制回收内存，完成连接释放
                            Coroutine::create(
                                function () {
                                    gc_collect_cycles();
                                }
                            );
                        }
                    );
                }

                /**
                 * 是否建立连接
                 * @param $connection
                 * @return bool
                 */
                public function isConnected($connection): bool
                {
                    return true;
                }

                /**
                 * 重置连接
                 * @param $connection
                 * @param array $config
                 */
                public function reset($connection, array $config)
                {

                }

                /**
                 * 验证连接
                 * @param $connection
                 * @return bool
                 */
                public function validate($connection): bool
                {
                    return true;
                }
            },
            []
        );

        $this->pool->init();
    }

    /**
     * 从连接池借用连接
     * @return mixed|null
     */
    protected function getPoolConnection()
    {
        return Context::rememberData(
            "connection." . spl_object_id($this),
            function () {
                //从连接池借用连接
                $connection = $this->pool->borrow();
                //标记连接释放情况
                $connection->{static::KEY_RELEASED} = false;
                //用于资源的释放，会在协程关闭之前 (即协程函数执行完毕时) 进行调用，就算抛出了异常，已注册的 defer 也会被执行
                Coroutine::defer(
                    function () use ($connection) {
                        //自动归还
                        $connection->{static::KEY_RELEASED} = true;
                        $this->pool->return($connection);
                    }
                );
                return $connection;
            }
        );
    }

    /**
     * 连接释放
     * @return mixed
     */
    public function release()
    {
        $connection = $this->getPoolConnection();
        if ($connection->{static::KEY_RELEASED}) {
            return;
        }
        $this->pool->return($connection);
    }

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $connection = $this->getPoolConnection();
        if ($connection->{static::KEY_RELEASED}) {
            throw new RuntimeException("连接已被释放");
        }
        return $connection->{$method}(...$arguments);
    }

}