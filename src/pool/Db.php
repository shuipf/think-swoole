<?php
// +----------------------------------------------------------------------
// | Db
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\pool;

use think\Config;
use think\db\ConnectionInterface;
use think\swoole\concerns\InteractsWithPool;
use think\swoole\coroutine\Context;

/**
 * Class Db
 * @package think\swoole\pool
 * @property Config $config
 */
class Db extends \think\Db
{
    use InteractsWithPool;

    /**
     * 获取最大连接数
     * @return int
     */
    protected function getPoolMaxActive($name): int
    {
        return $this->config->get('swoole.pool.db.max_active', 3);
    }

    /**
     * 获取最大超时时间
     * @return int
     */
    protected function getPoolMaxWaitTime($name): int
    {
        return $this->config->get('swoole.pool.db.max_wait_time', 3);
    }

    /**
     * 创建数据库连接实例
     * @access protected
     * @param string|null $name 连接标识
     * @param bool $force 强制重新连接
     * @return ConnectionInterface
     */
    protected function instance(string $name = null, bool $force = false): ConnectionInterface
    {
        if (empty($name)) {
            $name = $this->getConfig('default', 'mysql');
        }
        if ($force) {
            return $this->createConnection($name);
        }
        return Context::rememberData(
            "db.connection.{$name}",
            function () use ($name) {
                return $this->getPoolConnection($name);
            }
        );
    }

    /**
     * 创建连接池需要的Db连接对象
     * @param string $name
     * @return ConnectionInterface
     */
    protected function createPoolConnection(string $name)
    {
        return $this->createConnection($name);
    }

    /**
     * 获取连接配置
     * @param string $name
     * @return array
     */
    protected function getConnectionConfig(string $name): array
    {
        $config = parent::getConnectionConfig($name);
        //打开断线重连
        $config['break_reconnect'] = true;
        return $config;
    }

}