<?php
// +----------------------------------------------------------------------
// | Channel
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc\server;

use RuntimeException;
use Swoole\Coroutine;
use think\swoole\rpc\Packer;
use think\swoole\rpc\server\channel\Buffer;
use think\swoole\rpc\server\channel\File;

class Channel
{
    /**
     * @var
     */
    protected $header;

    /**
     * @var \Swoole\Coroutine\Channel
     */
    protected $queue;

    /**
     * Channel constructor.
     * @param $header
     */
    public function __construct($header)
    {
        switch ($header['type']) {
            case Packer::TYPE_BUFFER:
                $type = Buffer::class;
                break;
            case Packer::TYPE_FILE:
                $type = File::class;
                break;
            default:
                throw new RuntimeException("不支持的数据类型:[{$header['type']}");
        }

        $this->header = $header;
        $this->queue = new Coroutine\Channel(1);

        Coroutine::create(function () use ($type, $header) {
            $handle = new $type($header['length']);
            $this->queue->push($handle);
        });
    }

    /**
     * @return File|Buffer
     */
    public function pop()
    {
        return $this->queue->pop();
    }

    /**
     * @param $handle
     * @return mixed
     */
    public function push($handle)
    {
        return $this->queue->push($handle);
    }

    /**
     * @return mixed
     */
    public function close()
    {
        return $this->queue->close();
    }
}