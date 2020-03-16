<?php
// +----------------------------------------------------------------------
// | ParserInterface
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\contract\rpc;

use think\swoole\rpc\Protocol;

interface ParserInterface
{

    const EOF = "\r\n\r\n";

    /**
     * 编码
     * @param Protocol $protocol
     * @return string
     */
    public function encode(Protocol $protocol): string;

    /**
     * 解码
     * @param string $string
     * @return Protocol
     */
    public function decode(string $string): Protocol;

    /**
     * @param string $string
     * @return mixed
     */
    public function decodeResponse(string $string);

    /**
     * @param mixed $result
     * @return string
     */
    public function encodeResponse($result): string;
}