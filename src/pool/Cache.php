<?php
// +----------------------------------------------------------------------
// | Cache
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\pool;

use think\swoole\pool\proxy\Store;

class Cache extends \think\Cache
{
    /**
     * 创建驱动
     * @param string $name
     * @return mixed|Store
     */
    protected function createDriver(string $name)
    {
        return new Store(
            function () use ($name) {
                return parent::createDriver($name);
            },
            $this->app->config->get('swoole.pool.cache', [])
        );
    }

}