<?php
// +----------------------------------------------------------------------
// | Db
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\pool;

use think\Config;
use think\db\ConnectionInterface;
use think\db\PDOConnection;
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
    protected function getPoolMaxActive(): int
    {
        return $this->config->get('swoole.pool.db.max_active', 3);
    }

    /**
     * 获取最大超时时间
     * @return int
     */
    protected function getPoolMaxWaitTime(): int
    {
        return $this->config->get('swoole.pool.db.max_wait_time', 3);
    }

    /**
     * 最大活动时间
     * @return int
     */
    protected function getPoolMaxUseTime(): int
    {
        return $this->config->get('swoole.pool.db.max_use_time', 3600);
    }

    /**
     * 最大空闲时间
     * @return int
     */
    protected function getPoolMaxIdleTime(): int
    {
        return $this->config->get('swoole.pool.db.max_idle_time', 20);
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
        //创建连接
        return $this->createConnection($name);
    }

    /**
     * 移除连接
     * @param PDOConnection $connection
     * @return mixed
     */
    protected function removePoolConnection(PDOConnection $connection)
    {
        $connection->close();
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