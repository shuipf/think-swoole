<?php
// +----------------------------------------------------------------------
// | Packer
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc;

use RuntimeException;

class Packer
{
    public const HEADER_SIZE = 8;
    public const HEADER_STRUCT = "Nlength/Ntype";
    public const HEADER_PACK = "NN";

    public const TYPE_BUFFER = 0;
    public const TYPE_FILE = 1;

    /**
     * 打包
     * @param $data
     * @param int $type
     * @return string
     */
    public static function pack($data, $type = self::TYPE_BUFFER)
    {
        return pack(self::HEADER_PACK, strlen($data), $type) . $data;
    }

    /**
     * 解包
     * @param $data
     * @return array
     */
    public static function unpack($data)
    {
        $header = unpack(self::HEADER_STRUCT, substr($data, 0, self::HEADER_SIZE));
        if ($header === false) {
            throw new RuntimeException("Invalid Header");
        }
        $data = substr($data, self::HEADER_SIZE);
        return [$header, $data];
    }
}