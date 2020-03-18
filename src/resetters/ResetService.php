<?php
// +----------------------------------------------------------------------
// | ResetService 进行服务注册和启动
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\resetters;

use think\Container;
use think\swoole\concerns\ModifyProperty;
use think\swoole\contract\ResetterInterface;
use think\swoole\Sandbox;

/**
 * Class ResetService
 * @package think\swoole\resettersß
 * @property Container $app;
 */
class ResetService implements ResetterInterface
{

    use ModifyProperty;

    /**
     * "handle" function for resetting app.
     * @param Container $app
     * @param Sandbox $sandbox
     * @throws \ReflectionException
     */
    public function handle(Container $app, Sandbox $sandbox)
    {
        foreach ($sandbox->getServices() as $service) {
            $this->modifyProperty($service, $app);
            if (method_exists($service, 'register')) {
                $service->register();
            }
            if (method_exists($service, 'boot')) {
                $app->invoke([$service, 'boot']);
            }
        }
    }

}