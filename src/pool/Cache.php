<?php
// +----------------------------------------------------------------------
// | Cache
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\pool;

use think\swoole\concerns\InteractsWithPool;
use think\swoole\coroutine\Context;

class Cache extends \think\Cache
{
    use InteractsWithPool;

    /**
     * 获取最大连接数
     * @return int
     */
    protected function getPoolMaxActive($name): int
    {
        return $this->app->config->get('swoole.pool.cache.max_active', 3);
    }

    /**
     * 获取最大超时时间
     * @return int
     */
    protected function getPoolMaxWaitTime($name): int
    {
        return $this->app->config->get('swoole.pool.cache.max_wait_time', 3);
    }

    /**
     * 获取驱动实例
     * @param null|string $name
     * @return mixed
     */
    protected function driver(string $name = null)
    {
        $name = $name ?: $this->getDefaultDriver();
        return Context::rememberData(
            "cache.store.{$name}",
            function () use ($name) {
                return $this->getPoolConnection($name);
            }
        );
    }

    /**
     * 创建连接池需要的Cache连接对象
     * @param string $name
     * @return mixed
     */
    protected function createPoolConnection(string $name)
    {
        return $this->createDriver($name);
    }
}