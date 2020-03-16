<?php
// +----------------------------------------------------------------------
// | Protocol 调用协议
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc;

class Protocol
{

    const ACTION_INTERFACE = '@action_interface';

    const FILE = '@file';

    /**
     * 接口名称
     * @var string
     */
    private $interface = '';

    /**
     * 方法名
     * @var string
     */
    private $method = '';

    /**
     * 参数
     * @var array
     */
    private $params = [];

    /**
     * Replace constructor
     * @param string $interface
     * @param string $method
     * @param array $params
     * @return Protocol
     */
    public static function make(string $interface, string $method, array $params)
    {
        $instance = new static();
        $instance->interface = $interface;
        $instance->method = $method;
        $instance->params = $params;
        return $instance;
    }

    /**
     * @return string
     */
    public function getInterface(): string
    {
        return $this->interface;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

}
