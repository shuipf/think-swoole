<?php
// +----------------------------------------------------------------------
// | Buffer数据类型
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc\server\channel;

class Buffer
{
    /**
     * 数据
     * @var string
     */
    protected $data = '';

    /**
     * 长度
     * @var int
     */
    protected $length;

    /**
     * Buffer constructor.
     * @param int $length 长度
     */
    public function __construct($length)
    {
        $this->length = $length;
    }

    /**
     * 写入数据，如果数据大会多次写入
     * @param string $data 引用方式传入数据50
     * @return string
     */
    public function write(&$data)
    {
        //现有数据长度
        $size = strlen($this->data);
        //新数据
        $string = substr($data, 0, $this->length - $size);
        //追加新数据
        $this->data .= $string;
        //判断是否还有剩余数据
        if (strlen($data) >= $this->length - $size) {
            //通过引用的方式改变$data
            $data = substr($data, $this->length - $size);
            return $this->data;
        } else {
            $data = '';
        }
    }
}