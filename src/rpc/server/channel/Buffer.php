<?php
// +----------------------------------------------------------------------
// | Buffer
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc\server\channel;

class Buffer
{
    /**
     * @var string
     */
    protected $data = '';

    /**
     * @var int
     */
    protected $length;

    /**
     * Buffer constructor.
     * @param $length
     */
    public function __construct($length)
    {
        $this->length = $length;
    }

    /**
     * @param $data
     * @return string
     */
    public function write(&$data)
    {
        $size = strlen($this->data);
        $string = substr($data, 0, $this->length - $size);
        $this->data .= $string;
        if (strlen($data) >= $this->length - $size) {
            $data = substr($data, $this->length - $size);
            return $this->data;
        } else {
            $data = '';
        }
    }
}