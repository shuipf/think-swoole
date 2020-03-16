<?php
// +----------------------------------------------------------------------
// | Connector
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc\client;

use Generator;

interface Connector
{
    /**
     * @param Generator|string $data
     * @return string
     */
    public function sendAndRecv($data);
}