<?php
// +----------------------------------------------------------------------
// | WithApplication
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\concerns;

use Closure;
use Swoole\Server;
use think\App;
use think\swoole\App as SwooleApp;
use think\swoole\pool\Cache;
use think\swoole\pool\Db;
use think\swoole\Sandbox;
use Throwable;

/**
 * Trait WithApplication
 * @package think\swoole\concerns
 * @property App $container
 */
trait WithApplication
{

    use InteractsWithCoordinator;

    /**
     * 每个Worker进程里的
     * @var SwooleApp
     */
    protected $app;

    /**
     * 获取配置
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public function getConfig(string $name, $default = null)
    {
        return $this->container->config->get("swoole.{$name}", $default);
    }

    /**
     * 获取沙箱
     * @return Sandbox
     */
    public function getSandbox()
    {
        return $this->app->make(Sandbox::class);
    }

    /**
     * 在沙箱中执行
     * @param Closure $callable
     * @param null $fd
     * @param bool $persistent
     */
    public function runInSandbox(Closure $callable, $fd = null, $persistent = false)
    {
        try {
            $this->getSandbox()->run($callable, $fd, $persistent);
        } catch (Throwable $e) {
            $this->logServerError($e);
        }
    }

    /**
     * 监听事件
     * @param string $event
     * @param        $listener
     * @param bool $first
     */
    public function onEvent(string $event, $listener, bool $first = false): void
    {
        $this->container->event->listen("swoole.{$event}", $listener, $first);
    }

    /**
     * 触发事件
     * @param string $event
     * @param null $params
     */
    protected function triggerEvent(string $event, $params = null): void
    {
        $this->container->event->trigger("swoole.{$event}", $params);
    }

    /**
     * 应用准备
     * 在 Worker 进程 /Task 进程 都会进行初始化
     * 在这些进程神马周期里，$this->app 对象是同一个，且和命令行下启动的app对象不是同一个，这个是新启动，一直到进程结束
     * @return void
     */
    protected function prepareApplication()
    {
        if (!$this->app instanceof SwooleApp) {
            //实例化app对象
            $this->app = new SwooleApp($this->container->getRootPath());

            //绑定对象
            $this->app->bind(SwooleApp::class, App::class);
            $this->app->bind(Server::class, $this->getServer());
            $this->app->bind("swoole.server", Server::class);

            //绑定db、cache连接池，单独实现的一套，没走open-smf/connection-pool
            if ($this->getConfig('pool.db.enable', true)) {
                $this->app->bind('db', Db::class);
                $this->app->resolving(
                    Db::class,
                    function (Db $db) {
                        $db->setLog($this->container->log);
                    }
                );
            }
            if ($this->getConfig('pool.cache.enable', true)) {
                $this->app->bind('cache', Cache::class);
            }

            //初始化应用
            $this->app->initialize();
            $this->prepareConcretes();
        }
    }

    /**
     * 预加载
     * @return void
     */
    protected function prepareConcretes()
    {
        $defaultConcretes = ['db', 'cache', 'event'];
        $concretes = array_merge($defaultConcretes, $this->getConfig('concretes', []));
        foreach ($concretes as $concrete) {
            if ($this->app->has($concrete)) {
                $this->app->make($concrete);
            }
        }
    }
}