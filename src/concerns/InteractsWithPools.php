<?php
// +----------------------------------------------------------------------
// | InteractsWithPools
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\concerns;

use Smf\ConnectionPool\ConnectionPool;
use Smf\ConnectionPool\Connectors\ConnectorInterface;
use Swoole\Server;
use think\App;
use think\helper\Arr;
use think\swoole\Pool;

/**
 * Trait InteractsWithRpc
 * @package think\swoole\concerns
 * @property App $app
 * @method Server getServer()
 */
trait InteractsWithPools
{
    /**
     * 获取连接池管理对象
     * @return Pool
     */
    public function getPools()
    {
        return $this->app->make(Pool::class);
    }

    /**
     * 连接池准备
     * @return void
     */
    protected function preparePools()
    {
        $createPools = function () {
            /**
             * 获取连接池管理对象
             * @var Pool $pool
             */
            $pools = $this->getPools();
            //遍历需要开启连接池的数据
            foreach ($this->getConfig('pool', []) as $name => $config) {
                //连接池类型
                $type = Arr::pull($config, 'type');
                if ($type && is_subclass_of($type, ConnectorInterface::class)) {
                    $pool = new ConnectionPool(
                        Pool::pullPoolConfig($config),
                        $this->app->make($type),
                        $config
                    );
                    $pools->add($name, $pool);
                    //注入到app
                    $this->app->instance("swoole.pool.{$name}", $pool);
                }
            }
        };
        $closePools = function () {
            $this->getPools()->closeAll();
        };
        $this->onEvent('workerStart', $createPools);
        $this->onEvent('workerStop', $closePools);
        $this->onEvent('WorkerError', $closePools);
        $this->onEvent('WorkerExit', $closePools);
    }
}