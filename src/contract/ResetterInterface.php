<?php
// +----------------------------------------------------------------------
// | ResetterContract 定义重置接口
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\contract;

use think\Container;
use think\swoole\Sandbox;

interface ResetterInterface
{
    /**
     * "handle" function for resetting app.
     * @param Container $app 沙箱快照中的app对象
     * @param Sandbox $sandbox 沙箱对象
     */
    public function handle(Container $app, Sandbox $sandbox);
}
