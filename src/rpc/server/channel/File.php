<?php
// +----------------------------------------------------------------------
// | File数据类型
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc\server\channel;

class File
{
    /**
     * 临时文件名
     * @var false|string
     */
    protected $name;

    /**
     * 临时文件句柄
     * @var false|resource
     */
    protected $handle;

    /**
     * 长度
     * @var int
     */
    protected $length;

    /**
     * File constructor.
     * @param int $length 长度
     */
    public function __construct($length)
    {
        $this->name = tempnam(sys_get_temp_dir(), "swoole_rpc_");
        $this->handle = fopen($this->name, 'ab');
        $this->length = $length;
    }

    /**
     * 写入数据
     * @param string $data 引用方式传入数据
     * @return \think\swoole\rpc\File
     */
    public function write(&$data)
    {
        $size = fstat($this->handle)['size'];
        $string = substr($data, 0, $this->length - $size);
        fwrite($this->handle, $string);
        if (strlen($data) >= $this->length - $size) {
            fclose($this->handle);
            $data = substr($data, $this->length - $size);
            return new \think\swoole\rpc\File($this->name);
        } else {
            $data = '';
        }
    }
}