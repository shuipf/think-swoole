<?php
// +----------------------------------------------------------------------
// | Channel
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc\server;

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
        $this->header = $header;
        $this->queue = new \Swoole\Coroutine\Channel(1);
        go(
            function () use ($header) {
                switch ($header['type']) {
                    case Packer::TYPE_BUFFER:
                        $type = Buffer::class;
                        break;
                    case Packer::TYPE_FILE:
                        $type = File::class;
                        break;
                    default:
                        throw new \RuntimeException('not support data type');
                }
                $handle = new $type($header['length']);
                $this->queue->push($handle);
            }
        );
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