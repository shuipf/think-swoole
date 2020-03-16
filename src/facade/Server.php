<?php
// +----------------------------------------------------------------------
// | Server
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\facade;

use think\Facade;

/**
 * Class Server
 * @package think\swoole\facade
 */
class Server extends Facade
{
    /**
     * 获取当前门面对应类名
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'swoole.server';
    }
}
