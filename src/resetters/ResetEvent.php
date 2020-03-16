<?php
// +----------------------------------------------------------------------
// | ResetEvent 复制沙盒里的事件对象到容器event
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\resetters;

use think\Container;
use think\swoole\Sandbox;
use think\swoole\concerns\ModifyProperty;
use think\swoole\contract\ResetterInterface;

/**
 * Class ResetEvent
 * @package think\swoole\resetters
 * @property Container $app;
 */
class ResetEvent implements ResetterInterface
{

    use ModifyProperty;

    public function handle(Container $app, Sandbox $sandbox)
    {
        $event = clone $sandbox->getEvent();
        $this->modifyProperty($event, $app);
        $app->instance('event', $event);
        return $app;
    }
}
