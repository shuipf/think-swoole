<?php
// +----------------------------------------------------------------------
// | 配置
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

use think\facade\Env;
use think\facade\App;

return [
    //http服务配置
    'server' => [
        //监听地址
        'host' => '0.0.0.0',
        //监听端口
        'port' => 8050,
        //运行模式 默认为SWOOLE_PROCESS多进程模式
        'mode' => SWOOLE_PROCESS,
        //sock type 默认为SWOOLE_SOCK_TCP
        'sock_type' => SWOOLE_SOCK_TCP,
        //Http Server 参数
        'options' => [
            //master进程的PID
            'pid_file' => App::getRuntimePath() . 'swoole.pid',
            //指定swoole错误日志文件
            'log_file' => App::getRuntimePath() . 'swoole.log',
            //守护进程化
            'daemonize' => false,
            //通常，根据您的cpu核心，此值应该是1到4倍
            'reactor_num' => Env::get('swoole.reactor_num', 2),
            //设置启动的Worker进程数
            'worker_num' => Env::get('swoole.worker_num', 2),
            //设置启动的task进程数量
            'task_worker_num' => Env::get('swoole.task_worker_num', 2),
            //开启静态文件请求处理功能
            'enable_static_handler' => true,
            //配置静态文件根目录
            'document_root' => root_path('www'),
            //设置最大数据包尺寸，单位为字节
            'package_max_length' => 20 * 1024 * 1024,
            //配置发送输出缓存区内存尺寸
            'buffer_output_size' => 10 * 1024 * 1024,
            //配置客户端连接的缓存区长度
            'socket_buffer_size' => 128 * 1024 * 1024,
        ],
    ],
    //热更新配置
    'hot_update' => [
        //是否启用热更新
        'enable' => Env::get('swoole.hot_update', false),
        //监控文件类型
        'name' => ['*.php', '*.env'],
        //监控访问
        'include' => [
            App::getRootPath() . 'view' . DIRECTORY_SEPARATOR,
            App::getBasePath(),
        ],
        'exclude' => [],
    ],
    'rpc' => [
        'server' => [
            'enable' => true,
            'port' => 9050,
            'services' => [
            ],
        ],
        'client' => [
        ],
    ],
    //连接池
    'pool' => [
        'db' => [
            'enable' => true,
            'max_active' => Env::get('pool_db.max_active', 10),
            'max_wait_time' => 1,
        ],
        'cache' => [
            'enable' => true,
            'max_active' => Env::get('pool_cache.max_active', 20),
            'max_wait_time' => 1,
        ],
    ],
    //协程
    'coroutine' => [
        'enable' => true,
        'flags' => SWOOLE_HOOK_ALL,
    ],
    //沙箱模式下需要复位的对象
    'resetters' => [],
    //需要创建的内存表结构
    'tables' => [],
    //每次请求前需要清空的实例
    'instances' => [],
    //每次请求前需要重新执行的服务
    'services' => [],
];
