<?php
// +----------------------------------------------------------------------
// | InteractsWithPools 连接池准备，为扩展准备的
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\concerns;

use Exception;
use Smf\ConnectionPool\ConnectionPool;
use Smf\ConnectionPool\Connectors\ConnectorInterface;
use Swoole\Server;
use think\App;
use think\helper\Arr;
use think\swoole\Pool;
use Throwable;

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
                //是否定义type，如果有定义走自定义连接池实现，默认的 db cache 没有走这里
                if ($type && is_subclass_of($type, ConnectorInterface::class)) {
                    //实例这个连接池对象
                    $pool = new ConnectionPool(
                    //连接池配置
                        Pool::pullPoolConfig($config),
                        //连接器
                        $this->app->make($type),
                        //连接器配置
                        $config
                    );
                    $pools->add($name, $pool);
                    //注入到app
                    $this->app->instance("swoole.pool.{$name}", $pool);
                }
            }
        };
        $closePools = function () {
            //关闭全部连接池
            try {
                $this->getPools()->closeAll();
            } catch (Exception | Throwable $e) {

            }
        };
        $this->onEvent('workerStart', $createPools);
        $this->onEvent('workerStop', $closePools);
        $this->onEvent('workerError', $closePools);
        $this->onEvent('workerExit', $closePools);
    }
}