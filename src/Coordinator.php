<?php
// +----------------------------------------------------------------------
// | Coordinator
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use Swoole\Coroutine\Channel;

class Coordinator
{
    /**
     * 通道
     * @var Channel
     */
    private $channel;

    /**
     * Coordinator constructor.
     */
    public function __construct()
    {
        $this->channel = new Channel(1);
    }

    /**
     * @param int $timeout
     * @return bool
     */
    public function yield($timeout = -1): bool
    {
        $this->channel->pop((float) $timeout);
        return $this->channel->errCode === SWOOLE_CHANNEL_CLOSED;
    }

    /**
     * 关闭通道
     * @return void
     */
    public function resume(): void
    {
        $this->channel->close();
    }
}