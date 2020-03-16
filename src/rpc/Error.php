<?php
// +----------------------------------------------------------------------
// | Error
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc;

class Error implements \JsonSerializable
{
    /**
     * @var int
     */
    protected $code = 0;

    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @param int $code
     * @param string $message
     * @param mixed $data
     *
     * @return Error
     */
    public static function make(int $code, string $message, $data = null): self
    {
        $instance = new static();

        $instance->code = $code;
        $instance->message = $message;
        $instance->data = $data;

        return $instance;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function jsonSerialize()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
