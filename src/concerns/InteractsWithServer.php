<?php
// +----------------------------------------------------------------------
// | InteractsWithServer
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\concerns;

use Exception;
use Swoole\Process;
use Swoole\Runtime;
use Swoole\Server;
use Swoole\Server\Task;
use think\App;
use think\console\Output;
use think\Event;
use think\exception\Handle;
use think\helper\Str;
use think\swoole\FileWatcher;
use think\swoole\PidManager;
use Throwable;

/**
 * Trait InteractsWithServer
 * @package think\swoole\concerns
 * @property PidManager $pidManager
 * @property App $container
 */
trait InteractsWithServer
{
    /**
     * 启动服务
     * @return void
     */
    public function run(): void
    {
        $this->getServer()->set(
            [
                'task_enable_coroutine' => true,
                'send_yield' => true,
                'reload_async' => true,
                'enable_coroutine' => true,
                'max_request' => 0,
                'task_max_request' => 0,
            ]
        );
        $this->initialize();
        $this->triggerEvent('init');
        //热更新
        if ($this->getConfig('hot_update.enable', false)) {
            $this->addHotUpdateProcess();
        }
        //启动服务
        $this->getServer()->start();
    }

    /**
     * 停止服务
     * @return void
     */
    public function stop(): void
    {
        $this->getServer()->shutdown();
    }

    /**
     * "onStart" listener.
     * 启动后在主进程（master）的主线程回调此函数
     */
    public function onStart()
    {
        $this->setProcessName('master process');
        //记录主进程pid和管理进程的的pid
        $this->pidManager->create($this->getServer()->master_pid, $this->getServer()->manager_pid ?? 0);
        //触发事件 swoole.start
        $this->triggerEvent("start", func_get_args());
    }

    /**
     * The listener of "managerStart" event.
     * 当管理进程启动时触发此事件
     * @return void
     */
    public function onManagerStart()
    {
        $this->setProcessName('manager process');
        //触发事件 swoole.managerStart
        $this->triggerEvent("managerStart", func_get_args());
    }

    /**
     * "onWorkerStart" listener.
     * 此事件在 Worker 进程 /Task 进程启动时发生，这里创建的对象可以在进程生命周期内使用
     * @param \Swoole\Http\Server|mixed $server
     * @throws Exception
     */
    public function onWorkerStart($server)
    {
        //是否开启一键协程
        Runtime::enableCoroutine($this->getConfig('coroutine.enable', true), $this->getConfig('coroutine.flags', SWOOLE_HOOK_ALL));

        //清除apc、op缓存
        $this->clearCache();

        $this->setProcessName($server->taskworker ? 'task process' : 'worker process');

        $this->prepareApplication();

        $this->triggerEvent("workerStart", $this->app);
    }

    /**
     * Set onTask listener.
     * 在 task 进程内被调用
     * 在 task 进程内被调用
     * worker 进程可以使用 task 函数向 task_worker 进程投递新的任务
     * 当前的 Task 进程在调用 onTask 回调函数时会将进程状态切换为忙碌，这时将不再接收新的 Task，当 onTask 函数返回时会将进程状态切换为空闲然后继续接收新的 Task
     * @param mixed $server
     * @param Task $task
     * @param mixed $data
     */
    public function onTask($server, Task $task)
    {
        $this->runInSandbox(
            function (Event $event) use ($task) {
                $event->trigger('swoole.task', $task);
            }
        );
    }

    /**
     * Set onShutdown listener.
     * 此事件在 Server 正常结束时发生
     */
    public function onShutdown()
    {
        $this->triggerEvent('shutdown');
        $this->pidManager->remove();
    }

    /**
     * Add process to http server
     * @param Process $process
     */
    public function addProcess(Process $process): void
    {
        $this->getServer()->addProcess($process);
    }

    /**
     * 获取服务
     * @return Server
     */
    public function getServer()
    {
        return $this->container->make(Server::class);
    }

    /**
     * 日志服务器错误
     * @param Throwable|Exception $e
     */
    public function logServerError(Throwable $e)
    {
        /**
         * @var Handle $handle
         */
        $handle = $this->container->make(Handle::class);
        $handle->renderForConsole(new Output(), $e);
        $handle->report($e);
    }

    /**
     * 注册swoole各种事件回调
     * @return void
     */
    protected function setSwooleServerListeners()
    {
        foreach ($this->events as $event) {
            //下划线转驼峰 onStart
            $listener = Str::camel("on_{$event}");
            //是否已经有实现没有返回一个闭包处理
            $callback = method_exists($this, $listener) ? [$this, $listener] : function () use ($event) {
                $this->triggerEvent($event, func_get_args());
            };
            $this->getServer()->on($event, $callback);
        }
    }

    /**
     * 热更新
     * @return void
     */
    protected function addHotUpdateProcess()
    {
        $process = new Process(
            function () {
                $watcher = new FileWatcher(
                    $this->getConfig('hot_update.include', []),
                    $this->getConfig('hot_update.exclude', []),
                    $this->getConfig('hot_update.name', [])
                );
                $watcher->watch(
                    function () {
                        $this->getServer()->reload();
                    }
                );
            }, false, 0
        );
        $this->getServer()->addProcess($process);
    }

    /**
     * 清除apc、op缓存
     * @return void
     */
    protected function clearCache()
    {
        if (extension_loaded('apc')) {
            apc_clear_cache();
        }
        if (extension_loaded('Zend OPcache')) {
            opcache_reset();
        }
    }

    /**
     * 设置进程名
     * @param $process
     * @return void
     */
    protected function setProcessName($process)
    {
        // Mac OSX不支持进程重命名
        if (stristr(PHP_OS, 'DAR')) {
            return;
        }
        $serverName = 'swoole_http_server';
        $appName = $this->container->config->get('app.name', 'ThinkPHP');
        $name = sprintf('%s: %s for %s', $serverName, $process, $appName);
        swoole_set_process_name($name);
    }
}