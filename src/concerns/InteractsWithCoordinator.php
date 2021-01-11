<?php
// +----------------------------------------------------------------------
// | InteractsWithCoordinator
// +----------------------------------------------------------------------
// | Copyright (c) 2021 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\concerns;

use think\swoole\Coordinator;

trait InteractsWithCoordinator
{
    /**
     * @var Coordinator[]
     */
    protected $coordinators = [];

    /**
     * @param $name
     * @param $callback
     */
    public function resumeCoordinator($name, $callback)
    {
        $this->coordinators[$name] = new Coordinator();
        $callback();
        $this->coordinators[$name]->resume();
    }

    /**
     * @param $name
     * @param int $timeout
     */
    public function waitCoordinator($name, $timeout = -1)
    {
        if (isset($this->coordinators[$name])) {
            $this->coordinators[$name]->yield($timeout);
        }
    }
}