<?php
// +----------------------------------------------------------------------
// | File
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc\server\channel;

class File
{
    /**
     * @var false|string
     */
    protected $name;

    /**
     * @var false|resource
     */
    protected $handle;

    /**
     * @var
     */
    protected $length;

    /**
     * File constructor.
     * @param $length
     */
    public function __construct($length)
    {
        $this->name = tempnam(sys_get_temp_dir(), "swoole_rpc_");
        $this->handle = fopen($this->name, 'ab');
        $this->length = $length;
    }

    /**
     * @param $data
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