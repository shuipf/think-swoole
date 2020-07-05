<?php
// +----------------------------------------------------------------------
// | Db
// +----------------------------------------------------------------------
// | Copyright (c) 2020 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\pool;

use think\Config;
use think\db\ConnectionInterface;
use think\swoole\pool\proxy\Connection;

/**
 * Class Db
 * @package think\swoole\pool
 * @property Config $config
 */
class Db extends \think\Db
{

    /**
     * 创建连接
     * @param string $name
     * @return ConnectionInterface
     */
    protected function createConnection(string $name): ConnectionInterface
    {
        return new Connection(
            function () use ($name) {
                return parent::createConnection($name);
            },
            $this->config->get('swoole.pool.db', [])
        );
    }

    /**
     * 获取连接配置
     * @param string $name
     * @return array
     */
    protected function getConnectionConfig(string $name): array
    {
        $config = parent::getConnectionConfig($name);

        //打开断线重连
        $config['break_reconnect'] = true;
        return $config;
    }

}