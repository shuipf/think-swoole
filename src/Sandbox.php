<?php
// +----------------------------------------------------------------------
// | Sandbox沙盒
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use Closure;
use ReflectionObject;
use RuntimeException;
use think\App;
use think\Config;
use think\Container;
use think\Event;
use think\Http;
use think\swoole\concerns\ModifyProperty;
use think\swoole\contract\ResetterInterface;
use think\swoole\coroutine\Context;
use think\swoole\resetters\ClearInstances;
use think\swoole\resetters\ResetConfig;
use think\swoole\resetters\ResetEvent;
use think\swoole\resetters\ResetService;
use Throwable;
use think\swoole\App as SwooleApp;

class Sandbox
{

    use ModifyProperty;

    /**
     * 不同协程环境中的应用程序容器
     * @var SwooleApp[]
     */
    protected $snapshots = [];

    /**
     * 应用
     * @var SwooleApp
     */
    protected $app;

    /**
     * 配置
     * @var Config
     */
    protected $config;

    /**
     * 事件
     * @var Event
     */
    protected $event;

    /**
     * 已重置类
     * @var array
     */
    protected $resetters = [];

    /**
     * 服务列表
     * @var array
     */
    protected $services = [];

    /**
     * Sandbox constructor.
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->setBaseApp($app);
        $this->initialize();
    }

    /**
     * 沙箱初始化
     * @return $this
     */
    public function initialize()
    {
        //设置当前应用对象为容器实例
        Container::setInstance(
            function () {
                return $this->getApplication();
            }
        );
        $this->app->bind(Http::class, \think\swoole\Http::class);
        //初始化配置
        $this->setInitialConfig();
        //初始化服务
        $this->setInitialServices();
        //初始化事件
        $this->setInitialEvent();
        //初始化复位
        $this->setInitialResetters();
        return $this;
    }

    /**
     * 设置
     * @param Container $app
     * @return $this
     */
    public function setBaseApp(Container $app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * 获取当前app对象（非沙箱内）
     * @return App
     */
    public function getBaseApp()
    {
        return $this->app;
    }

    /**
     * 执行请求
     * @param Closure $callable
     * @param null $fd
     * @param bool $persistent
     * @throws Throwable
     */
    public function run(Closure $callable, $fd = null, $persistent = false)
    {
        $this->init($fd);
        try {
            $this->getApplication()->invoke($callable, [$this]);
        } catch (Throwable $e) {
            throw $e;
        } finally {
            $this->clear(!$persistent);
        }
    }

    /**
     * 运行初始化
     * @param null $fd
     * @throws \ReflectionException
     */
    public function init($fd = null)
    {
        if (!is_null($fd)) {
            Context::setData('_fd', $fd);
        }
        $app = $this->getApplication();
        //设置app相关实例对象
        $this->setInstance($app);
        //重置应用
        $this->resetApp($app);
    }

    /**
     * 获取应用对象（沙箱克隆方式）
     * @return mixed|App|null
     */
    public function getApplication()
    {
        $snapshot = $this->getSnapshot();
        if ($snapshot instanceof Container) {
            return $snapshot;
        }

        //克隆当前应用对象到快照
        $snapshot = clone $this->getBaseApp();
        $this->setSnapshot($snapshot);

        return $snapshot;
    }

    /**
     * 获取快照
     * @return SwooleApp
     */
    public function getSnapshot()
    {
        return $this->snapshots[$this->getSnapshotId()] ?? null;
    }

    /**
     * 设置快照
     * @param Container $snapshot
     * @return $this
     */
    public function setSnapshot(Container $snapshot)
    {
        $this->snapshots[$this->getSnapshotId()] = $snapshot;
        return $this;
    }

    /**
     * 清理快照
     * @param bool $snapshot
     * @throws \ReflectionException
     */
    public function clear($snapshot = true)
    {
        if ($snapshot && $app = $this->getSnapshot()) {
            $app->clearInstances();
            unset($this->snapshots[$this->getSnapshotId()]);
        }
        Context::clear();
        $this->setInstance($this->getBaseApp());
    }

    /**
     * 绑定app类实例到容器
     * @param Container $app
     * @throws \ReflectionException
     */
    public function setInstance(Container $app)
    {
        $app->instance('app', $app);
        $app->instance(Container::class, $app);
        //通过反射的方式获取注册的系统服务
        $reflectObject = new ReflectionObject($app);
        $reflectProperty = $reflectObject->getProperty('services');
        $reflectProperty->setAccessible(true);
        $services = $reflectProperty->getValue($app);
        //遍历所有的服务，重新绑定app对象为当前快照的$app对象
        foreach ($services as $service) {
            //设置受保护的app对象属性
            $this->modifyProperty($service, $app, 'app');
        }
    }

    /**
     * 获取快照ID
     * @return int|string
     */
    protected function getSnapshotId()
    {
        if ($fd = Context::getData('_fd')) {
            return "fd_" . $fd;
        } else {
            return Context::getCoroutineId();
        }
    }

    /**
     * 初始化配置
     * @return void
     */
    protected function setInitialConfig()
    {
        $this->config = clone $this->getBaseApp()->config;
    }

    /**
     * 初始化事件
     * @return void
     */
    protected function setInitialEvent()
    {
        $this->event = clone $this->getBaseApp()->event;
    }

    /**
     * 获取配置对象
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 获取事件对象
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * 获取初始化后的服务列表
     * @return array
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * 初始化服务
     * @return void
     */
    protected function setInitialServices()
    {
        $app = $this->getBaseApp();
        $services = $this->config->get('swoole.services', []);
        foreach ($services as $service) {
            if (class_exists($service) && !in_array($service, $this->services)) {
                $serviceObj = new $service($app);
                $this->services[$service] = $serviceObj;
            }
        }
    }

    /**
     * 初始化重置器
     * @return void
     */
    protected function setInitialResetters()
    {
        $app = $this->getBaseApp();
        $resetters = [
            ClearInstances::class,
            ResetConfig::class,
            ResetEvent::class,
            ResetService::class,
        ];
        $resetters = array_merge($resetters, $this->config->get('swoole.resetters', []));
        foreach ($resetters as $resetter) {
            $resetterClass = $app->make($resetter);
            if (!$resetterClass instanceof ResetterInterface) {
                throw new RuntimeException("{$resetter} must implement " . ResetterInterface::class);
            }
            $this->resetters[$resetter] = $resetterClass;
        }
    }

    /**
     * 重置应用
     * @param Container $app
     * @return void
     */
    protected function resetApp(Container $app)
    {
        foreach ($this->resetters as $resetter) {
            $resetter->handle($app, $this);
        }
    }

}
