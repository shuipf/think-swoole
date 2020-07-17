<?php
// +----------------------------------------------------------------------
// | Manager
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use think\App;
use think\swoole\concerns\InteractsWithHttp;
use think\swoole\concerns\InteractsWithPools;
use think\swoole\concerns\InteractsWithRpcServer;
use think\swoole\concerns\InteractsWithRpcClient;
use think\swoole\concerns\InteractsWithServer;
use think\swoole\concerns\InteractsWithSwooleTable;
use think\swoole\concerns\WithApplication;

class Manager
{
    use InteractsWithServer,
        InteractsWithSwooleTable,
        InteractsWithHttp,
        InteractsWithPools,
        InteractsWithRpcClient,
        InteractsWithRpcServer,
        WithApplication;

    /**
     * @var App
     */
    protected $container;

    /**
     * Server events.
     * @var array
     */
    protected $events = [
        'start',
        'shutDown',
        'workerStart',
        'workerStop',
        'workerError',
        'workerExit',
        'packet',
        'task',
        'finish',
        'pipeMessage',
        'managerStart',
        'managerStop',
        'request',
    ];

    /**
     * Manager constructor.
     * @param App $container
     * @param PidManager $pidManager
     */
    public function __construct(App $container)
    {
        $this->container = $container;
    }

    /**
     * 初始化
     * @return void
     */
    protected function initialize(): void
    {
        //内存表准备
        $this->prepareTables();
        //连接池准备
        $this->preparePools();
        //设置swoole服务的事件监听
        $this->setSwooleServerListeners();
        //rpc准备
        $this->prepareRpcServer();
        $this->prepareRpcClient();
    }

}