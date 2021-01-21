<?php
// +----------------------------------------------------------------------
// | Job
// +----------------------------------------------------------------------
// | Copyright (c) 2021 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole;

class Job
{
    /**
     * 任务名称，支持闭包、类等，具体看框架invoke方法
     * @var array|string|Closure
     */
    public $name;

    /**
     * 参数
     * @var array
     */
    public $params;

    /**
     * Job constructor.
     * @param $name
     * @param $params
     */
    public function __construct($name, $params)
    {
        $this->name = $name;
        $this->params = $params;
    }
}