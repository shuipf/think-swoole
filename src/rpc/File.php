<?php
// +----------------------------------------------------------------------
// | File
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc;

use Throwable;

class File extends \think\File
{
    /**
     * 回收
     * @return void
     */
    public function __destruct()
    {
        //销毁时删除临时文件
        try {
            if (file_exists($this->getPathname())) {
                unlink($this->getPathname());
            }
        } catch (Throwable $e) {
        }
    }
}