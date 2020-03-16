<?php
// +----------------------------------------------------------------------
// | ClearInstances 清理容器中的对象实例
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\resetters;

use think\Container;
use think\swoole\Sandbox;
use think\swoole\contract\ResetterInterface;

class ClearInstances implements ResetterInterface
{
    public function handle(Container $app, Sandbox $sandbox)
    {
        $instances = ['log'];

        $instances = array_merge($instances, $sandbox->getConfig()->get('swoole.instances', []));

        foreach ($instances as $instance) {
            $app->delete($instance);
        }

        return $app;
    }
}
