<?php
// +----------------------------------------------------------------------
// | PidManager
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use Swoole\Coroutine;
use Swoole\Server;
use Swoole\Server\Port;
use think\App;
use think\Event;
use think\helper\Str;
use think\swoole\concerns\InteractsWithPools;
use think\swoole\concerns\InteractsWithRpcClient;
use think\swoole\concerns\InteractsWithServer;
use think\swoole\concerns\InteractsWithSwooleTable;
use think\swoole\concerns\WithApplication;
use think\swoole\contract\rpc\ParserInterface;
use think\swoole\rpc\Error;
use think\swoole\rpc\JsonParser;
use think\swoole\rpc\Packer;
use think\swoole\rpc\server\Channel;
use think\swoole\rpc\server\Dispatcher;
use Throwable;

class RpcManager
{
    use InteractsWithServer,
        InteractsWithSwooleTable,
        InteractsWithPools,
        InteractsWithRpcClient,
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
    ];

    /**
     * RPC事件
     * @var array
     */
    protected $rpcEvents = [
        'connect',
        'receive',
        'close',
    ];

    /**
     * @var Channel[]
     */
    protected $channels = [];

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
        $this->events = array_merge($this->events ?? [], $this->rpcEvents);
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

    /**
     * RPC服务准备
     * @return void
     */
    protected function prepareRpcServer()
    {
        $this->onEvent(
            'workerStart',
            function () {
                //解析器
                $this->bindRpcParser();
                //调度
                $this->bindRpcDispatcher();
            }
        );
    }

    /**
     * @param Port $port
     */
    public function attachToServer(Port $port)
    {
        //重置配置
        $port->set([]);
        //绑定事件回调
        foreach ($this->rpcEvents as $event) {
            //下划线转驼峰 onStart
            $listener = Str::camel("on_{$event}");
            //是否已经有实现没有返回一个闭包处理
            $callback = method_exists($this, $listener) ? [$this, $listener] : function () use ($event) {
                $this->triggerEvent("rpc.{$event}", func_get_args());
            };
            $port->on($event, $callback);
        }
        $this->onEvent(
            'workerStart',
            function (App $app) {
                $this->app = $app;
            }
        );
        $this->prepareRpcServer();
    }

    /**
     * RPC调度
     * @return void
     */
    protected function bindRpcDispatcher()
    {
        $services = $this->getConfig('rpc.server.services', []);
        $middleware = $this->getConfig('rpc.server.middleware', []);
        $this->app->make(Dispatcher::class, [$services, $middleware]);
    }

    /**
     * 绑定RPC解析器
     * @return void
     */
    protected function bindRpcParser()
    {
        $parserClass = $this->getConfig('rpc.server.parser', JsonParser::class);
        $this->app->bind(ParserInterface::class, $parserClass);
        $this->app->make(ParserInterface::class);
    }

    /**
     * @param Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onConnect(Server $server, int $fd, int $reactorId)
    {
        $args = func_get_args();
        $this->runInSandbox(
            function (Event $event) use ($args) {
                $event->trigger("swoole.rpc.Connect", $args);
            }, $fd, true
        );
    }

    /**
     * @param Server $server
     * @param $fd
     * @param $data
     * @param $callback
     * @return mixed
     */
    protected function recv(Server $server, $fd, $data, $callback)
    {
        if (!isset($this->channels[$fd]) || empty($handle = $this->channels[$fd]->pop())) {
            //解析包头
            try {
                [$header, $data] = Packer::unpack($data);
                $this->channels[$fd] = new Channel($header);
            } catch (Throwable $e) {
                //错误的包头
                Coroutine::create($callback, Error::make(Dispatcher::INVALID_REQUEST, $e->getMessage()));
                return $server->close($fd);
            }
            $handle = $this->channels[$fd]->pop();
        }
        $result = $handle->write($data);
        if (!empty($result)) {
            Coroutine::create($callback, $result);
            $this->channels[$fd]->close();
        } else {
            $this->channels[$fd]->push($handle);
        }
        if (!empty($data)) {
            $this->recv($server, $fd, $data, $callback);
        }
    }

    /**
     * 接收事件
     * @param Server $server
     * @param $fd
     * @param $reactorId
     * @param $data
     */
    public function onReceive(Server $server, $fd, $reactorId, $data)
    {
        $this->waitCoordinator('workerStart');
        $this->recv(
            $server, $fd, $data,
            function ($data) use ($fd) {
                $this->runInSandbox(
                    function (Dispatcher $dispatcher) use ($fd, $data) {
                        $dispatcher->dispatch($fd, $data);
                    },
                    $fd, true
                );
            }
        );
    }

    /**
     * @param Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose(Server $server, int $fd, int $reactorId)
    {
        unset($this->channels[$fd]);
        $args = func_get_args();
        $this->runInSandbox(
            function (Event $event) use ($args) {
                $event->trigger("swoole.rpc.Close", $args);
            }, $fd
        );
    }
}