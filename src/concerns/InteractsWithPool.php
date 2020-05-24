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
use think\facade\Log;
use think\helper\Str;

trait InteractsWithPool
{
    /**
     * 连接池列表，常见的cache、db
     * @var Channel[]
     */
    protected $pools = [];

    /**
     * 使用中的连接
     * @var array
     */
    protected $usePools = [];

    /**
     * 记录连接池当前连接数
     * @var array
     */
    protected $connectionCount = [];

    /**
     * 定时器
     * @var array
     */
    protected $balancerTimerId = [];

    /**
     * 获取连接池通道
     * @param string $name
     * @return Channel
     */
    protected function getPool($name)
    {
        if (empty($this->pools[$name])) {
            $this->pools[$name] = new Channel($this->getPoolMaxActive());
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
        //启动定时任务
        if (!isset($this->balancerTimerId[$name]) || $this->balancerTimerId[$name] == 0) {
            $this->balancerTimerId[$name] = $this->startBalanceTimer($name);
        }
        //判断连接池中的连接数
        if ($pool->isEmpty() && $this->connectionCount[$name] < $this->getPoolMaxActive()) {
            //新建链接
            $this->connectionCount[$name]++;
            $connection = $this->createPool($this->createPoolConnection($name), Str::random(12));
        } else {
            //从连接池取一个链接
            $connection = $pool->pop($this->getPoolMaxWaitTime());
            if ($connection === false) {
                throw new RuntimeException(
                    sprintf(
                        "%s 获取连接超时 %.2f(s), 当前连接池连接数: %d, 全部连接数: %d",
                        $name,
                        $this->getPoolMaxWaitTime(),
                        $pool->length(),
                        $this->connectionCount[$name] ?? 0
                    )
                );
            }
        }
        //记录使用中的连接
        $this->usePools[$name][$connection->getNumber()] = $connection;
        return $this->wrapProxy($pool, $connection, $name);
    }

    /**
     * 定时器
     * @param string $name
     * @return int
     */
    protected function startBalanceTimer($name)
    {
        return swoole_timer_tick(
            5 * 1000,
            function () use ($name) {
                $now = time();
                //有效
                $validConnections = [];
                $pool = $this->getPool($name);
                //最大空闲时间
                $maxIdleTime = $this->getPoolMaxIdleTime();
                while (true) {
                    //池子是空的跳过
                    if ($pool->isEmpty()) {
                        break;
                    }
                    $connection = $pool->pop(0.001);
                    //最后使用时间
                    $lastActiveTime = $connection->getConnectionUseTime();
                    //连接的最大空闲时间，达到该时间后，将从池中删除该连接
                    if ($now - $lastActiveTime < $maxIdleTime) {
                        $validConnections[] = $connection;
                    } else {
                        $this->removeConnection($name, $connection);
                    }
                }
                //重新放回池子
                foreach ($validConnections as $validConnection) {
                    $ret = $pool->push($validConnection, 0.001);
                    if ($ret === false) {
                        $this->removeConnection($name, $validConnection);
                    }
                }
                //检查使用中的连接
                foreach ($this->usePools[$name] as $number => $connection) {
                    //最后使用时间
                    $lastActiveTime = $connection->getConnectionUseTime();
                    //连接取出去使用中超时时间，达到该时间后，强制销毁，避免连接无法回收
                    if ($now - $lastActiveTime > 60) {
                        $this->removeConnection($name, $connection);
                        //日志
                        Log::record(
                            [
                                'tips' => '连接池使用超时',
                                'number' => $number,
                                'name' => $name,
                                'useCount' => isset($this->usePools[$name]) ? count($this->usePools[$name]) : 0,
                                'connectionCount' => isset($this->connectionCount[$name]) ? $this->connectionCount[$name] : 0,
                            ],
                            'error'
                        );
                    }
                }
            }
        );
    }

    /**
     * 创建连接池连接对象
     * @param object $connection 连接
     * @param string $number 编号
     * @return __anonymous@4112
     */
    protected function createPool($connection, string $number)
    {
        return new class($connection, $number) {

            /**
             * 连接编号
             * @var string
             */
            protected $number = '';

            /**
             * 连接对象
             * @var object
             */
            protected $connection;

            /**
             * 连接创建时间
             * @var int
             */
            protected $connectionCreationTime = 0;

            /**
             * 连接最后一次使用时间
             * @var int
             */
            protected $connectionUseTime = 0;

            /**
             *  constructor.
             * @param object $connection
             * @param null|string $number
             */
            public function __construct($connection, string $number = null)
            {
                $this->connection = $connection;
                $this->number = (string)(is_null($number) ? Str::random(12) : $number);
                $this->connectionCreationTime = time();
            }

            /**
             * 获取连接编号
             * @return string
             */
            public function getNumber(): string
            {
                return $this->number;
            }

            /**
             * 获取连接
             * @return object
             */
            public function getConnection()
            {
                return $this->connection;
            }

            /**
             * 获取创建时间
             * @return int
             */
            public function getConnectionCreationTime(): int
            {
                return $this->connectionCreationTime;
            }

            /**
             * 获取最后使用时间
             * @return int
             */
            public function getConnectionUseTime(): int
            {
                return $this->connectionUseTime;
            }

            /**
             * 更新使用时间
             * @return int
             */
            public function useTime()
            {
                $this->connectionUseTime = time();
                return $this;
            }
        };
    }

    /**
     * 移除连接
     * @param string $name
     * @param object $connection
     * @return bool
     */
    public function removeConnection($name, $connection): bool
    {
        $this->connectionCount[$name]--;
        //因为有强制收回，可能出现负数情况
        if ($this->connectionCount[$name] < 0) {
            $this->connectionCount[$name] = 0;
        }
        go(
            function () use ($name, $connection) {
                try {
                    //从使用中去除
                    unset($this->usePools[$name][$connection->getNumber()]);
                    //回调
                    $this->removePoolConnection($connection->getConnection());
                } catch (\Throwable $e) {
                    //忽略此异常.
                    //日志
                    Log::record(
                        [
                            'tips' => '连接池移除连接失败',
                            'number' => $connection->getNumber(),
                            'name' => $name,
                            'useCount' => isset($this->usePools[$name]) ? count($this->usePools[$name]) : 0,
                            'connectionCount' => isset($this->connectionCount[$name]) ? $this->connectionCount[$name] : 0,
                            'code' => $e->getCode(),
                            'file' => $e->getFile(),
                            'message' => $e->getMessage(),
                            'line' => $e->getLine(),
                        ],
                        'error'
                    );
                }
            }
        );
        return true;
    }

    /**
     * 代理连接，并增加资源回收
     * @param Channel $pool 管道对象
     * @param object $connection 连接对接
     * @param string $name 连接别名
     * @return mixed
     */
    protected function wrapProxy(Channel $pool, $connection, $name)
    {
        //手册说明 https://wiki.swoole.com/#/coroutine/coroutine?id=defer
        //用于资源的释放，会在协程关闭之前 (即协程函数执行完毕时) 进行调用，就算抛出了异常，已注册的 defer 也会被执行
        //自动归还
        defer(
            function () use ($pool, $connection, $name) {
                $ref = false;
                //判断通道是否已满
                if (!$pool->isFull()) {
                    //判断最大使用时间
                    if (time() - $connection->getConnectionCreationTime() < $this->getPoolMaxUseTime()) {
                        //向通道添加连接
                        $ref = $pool->push($connection, 0.001);
                    }
                }
                //关闭连接
                if (true !== $ref) {
                    $this->removeConnection($name, $connection);
                }
            }
        );
        //使用时间
        $connection->useTime();
        return $connection->getConnection();
    }

    /**
     * 创建连接池需要的链接对象
     * @param string $name
     * @return mixed
     */
    abstract protected function createPoolConnection(string $name);

    /**
     * 移除连接
     * @param $connection
     * @return mixed
     */
    abstract protected function removePoolConnection($connection);

    /**
     * 连接池最大活动连接数
     * @return int
     */
    abstract protected function getPoolMaxActive(): int;

    /**
     * 最大等待时间
     * @return int
     */
    abstract protected function getPoolMaxWaitTime(): int;

    /**
     * 最大活动时间
     * @return int
     */
    abstract protected function getPoolMaxUseTime(): int;

    /**
     * 最大空闲时间
     * @return int
     */
    abstract protected function getPoolMaxIdleTime(): int;

}