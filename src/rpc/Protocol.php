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
     * @var array
     */
    private $context = [];

    /**
     * Replace constructor
     * @param string $interface
     * @param string $method
     * @param array $params
     * @param array $context
     * @return Protocol
     */
    public static function make(string $interface, string $method, array $params, array $context = [])
    {
        $instance = new static();
        $instance->interface = $interface;
        $instance->method = $method;
        $instance->params = $params;
        $instance->context = $context;
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

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param string $interface
     */
    public function setInterface(string $interface): void
    {
        $this->interface = $interface;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * @param array $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }
}
