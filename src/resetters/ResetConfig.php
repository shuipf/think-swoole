<?php
// +----------------------------------------------------------------------
// | ResetConfig 复制沙盒里的配置对象到容器config
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\resetters;

use think\Container;
use think\swoole\Sandbox;
use think\swoole\contract\ResetterInterface;

class ResetConfig implements ResetterInterface
{

    /**
     * @param Container $app
     * @param Sandbox $sandbox
     * @return Container
     */
    public function handle(Container $app, Sandbox $sandbox)
    {
        $app->instance('config', clone $sandbox->getConfig());
        return $app;
    }
}
