<?php
// +----------------------------------------------------------------------
// | Http
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use think\Middleware;
use think\Route;
use think\swoole\concerns\ModifyProperty;

/**
 * Class Http
 * @package think\swoole
 * @property $request
 */
class Http extends \think\Http
{

    use ModifyProperty;

    /**
     * 中间件
     * @var Middleware
     */
    protected static $middleware;

    /**
     * 路由
     * @var Route
     */
    protected static $route;

    /**
     * 加载中间件
     * @throws \ReflectionException
     */
    protected function loadMiddleware(): void
    {
        if (!isset(self::$middleware)) {
            parent::loadMiddleware();
            self::$middleware = clone $this->app->middleware;
            $this->modifyProperty(self::$middleware, null, 'app');
        }

        $middleware = clone self::$middleware;
        $this->modifyProperty($middleware, $this->app, 'app');
        $this->app->instance("middleware", $middleware);
    }

    /**
     * 加载路由
     * 会缓存路由对象，每次请求都使用同一个路由对象
     * @throws \ReflectionException
     */
    protected function loadRoutes(): void
    {
        if (!isset(self::$route)) {
            parent::loadRoutes();
            self::$route = clone $this->app->route;
            //清理路由对象里的app对象
            $this->modifyProperty(self::$route, null, 'app');
            //清理路由对象里的request对象
            $this->modifyProperty(self::$route, null, 'request');
        }
    }

    /**
     * 根据请求进行路由调度
     * @param $request
     * @return \think\Response
     * @throws \ReflectionException
     */
    protected function dispatchToRoute($request)
    {
        //如果路由对象已经初始化
        if (isset(self::$route)) {
            //重新克隆一个路由对象
            $newRoute = clone self::$route;
            $this->modifyProperty($newRoute, $this->app, 'app');
            $this->app->instance("route", $newRoute);
        }
        return parent::dispatchToRoute($request);
    }

}
