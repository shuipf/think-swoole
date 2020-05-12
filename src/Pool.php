<?php
// +----------------------------------------------------------------------
// | Pool 连接池管理
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use Smf\ConnectionPool\ConnectionPool;
use think\helper\Arr;

class Pool
{
    /**
     * 连接池列表
     * @var array
     */
    protected $pools = [];

    /**
     * 添加
     * @param string $name 连接池名称
     * @param ConnectionPool $pool 连接池对象
     * @return Pool
     */
    public function add(string $name, ConnectionPool $pool)
    {
        $pool->init();
        $this->pools[$name] = $pool;
        return $this;
    }

    /**
     * 获取
     * @param string $name 连接池名称
     * @return ConnectionPool
     */
    public function get(string $name)
    {
        return $this->pools[$name] ?? null;
    }

    /**
     * 关闭某个连接池
     * @param string $name 连接池名称
     * @return mixed
     */
    public function close(string $name)
    {
        return $this->pools[$name]->close();
    }

    /**
     * 获取全部连接池
     * @return array
     */
    public function getAll()
    {
        return $this->pools;
    }

    /**
     * 关闭全部连接池
     * @return void
     */
    public function closeAll()
    {
        foreach ($this->pools as $pool) {
            $pool->close();
        }
    }

    /**
     * @param string $key
     * @return ConnectionPool
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * 获取连接池配置
     * @param $config
     * @return array
     */
    public static function pullPoolConfig(&$config)
    {
        return [
            //最小活动连接数
            'minActive' => Arr::pull($config, 'min_active', 0),
            //最大活动连接数
            'maxActive' => Arr::pull($config, 'max_active', 10),
            //最大等待时间
            'maxWaitTime' => Arr::pull($config, 'max_wait_time', 5),
            //最大空闲时间
            'maxIdleTime' => Arr::pull($config, 'max_idle_time', 20),
            //空闲检查间隔时间
            'idleCheckInterval' => Arr::pull($config, 'idle_check_interval', 10),
        ];
    }
}