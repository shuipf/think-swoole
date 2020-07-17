<?php
// +----------------------------------------------------------------------
// | App
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use think\swoole\coroutine\Context;

class App extends \think\App
{
    /**
     * 是否运行在命令行下
     * @return bool
     */
    public function runningInConsole()
    {
        return Context::hasData('_fd');
    }

}
