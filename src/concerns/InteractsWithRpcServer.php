<?php
// +----------------------------------------------------------------------
// | InteractsWithRpcServer
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\concerns;

use Swoole\Server;
use think\App;
use think\swoole\Pool;
use think\swoole\RpcManager;

/**
 * Trait InteractsWithRpc
 * @package think\swoole\concerns
 * @property App $app
 * @property App $container
 * @method Server getServer()
 * @method Pool getPools()
 */
trait InteractsWithRpcServer
{
    /**
     * rpc服务启动
     * @return void
     */
    protected function prepareRpcServer()
    {
        //是否开启rpc服务端
        if (!$this->getConfig('rpc.server.enable', false)) {
            return;
        }
        $host = $this->getConfig('server.host');
        $port = $this->getConfig('rpc.server.port', 9000);
        //增加rpc监听的端口服务
        $rpcServer = $this->getServer()->addlistener($host, $port, SWOOLE_SOCK_TCP);
        /**
         * rpc服务
         * @var RpcManager $rpcManager
         */
        $rpcManager = $this->container->make(RpcManager::class);
        $rpcManager->attachToServer($rpcServer);
    }
}