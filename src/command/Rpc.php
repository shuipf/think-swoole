<?php
// +----------------------------------------------------------------------
// | Rpc
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\swoole\PidManager;
use think\swoole\RpcManager;

class Rpc extends Command
{
    /**
     * 配置指令
     * @return void
     */
    public function configure()
    {
        $this->setName('swoole:rpc')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload", 'start')
            ->setDescription('启动Swoole RPC服务');
    }

    /**
     * 初始化
     * @param Input $input
     * @param Output $output
     */
    protected function initialize(Input $input, Output $output)
    {
        $this->app->bind(
            \Swoole\Server::class,
            function () {
                return $this->createSwooleServer();
            }
        );
        $this->app->bind(
            PidManager::class,
            function () {
                return new PidManager($this->app->config->get("swoole.server.options.pid_file"));
            }
        );
    }

    /**
     * 执行指令
     * @return void
     */
    public function handle()
    {
        $this->checkEnvironment();
        $action = $this->input->getArgument('action');
        if (in_array($action, ['start', 'stop', 'reload', 'restart'])) {
            $this->app->invokeMethod([$this, $action], [], true);
        } else {
            $this->output->writeln("<error>参数操作无效:{$action}, 支持 start|stop|restart|reload .</error>");
        }
    }

    /**
     * 检查环境
     * @return void
     */
    protected function checkEnvironment()
    {
        if (!extension_loaded('swoole')) {
            $this->output->error('没有安装swoole扩展');
            exit(1);
        }
        if (!version_compare(swoole_version(), '4.3.1', 'ge')) {
            $this->output->error('您的Swoole版本必须高于“4.3.1”');
            exit(1);
        }
    }

    /**
     * 启动server
     * @access protected
     * @param RpcManager $manager
     * @param PidManager $pidManager
     * @return void
     */
    protected function start(RpcManager $manager, PidManager $pidManager)
    {
        if ($pidManager->isRunning()) {
            $this->output->writeln('<error>Swoole RPC服务器进程已经运行.</error>');
            return;
        }
        $this->output->writeln('启动Swoole RPC服务器...');
        $host = $this->app->config->get('swoole.server.host');
        $port = $this->app->config->get('swoole.rpc.server.port');
        $this->output->writeln("Swoole RPC服务器已启动: <tcp://{$host}:{$port}>");
        $this->output->writeln('退出可以按 <info>`CTRL-C`</info>');
        $manager->run();
    }

    /**
     * 柔性重启server
     * @access protected
     * @param PidManager $manager
     * @return void
     */
    protected function reload(PidManager $manager)
    {
        if (!$manager->isRunning()) {
            $this->output->writeln('<error>没有Swoole RPC服务在运行.</error>');
            return;
        }
        $this->output->writeln('重新加载Swoole RPC服务中...');
        if (!$manager->killProcess(SIGUSR1)) {
            $this->output->error('> 失败');
            return;
        }
        $this->output->writeln('> 成功');
    }

    /**
     * 停止server
     * @access protected
     * @param PidManager $manager
     * @return void
     */
    protected function stop(PidManager $manager)
    {
        if (!$manager->isRunning()) {
            $this->output->writeln('<error>没有Swoole RPC服务在运行.</error>');
            return;
        }
        $this->output->writeln('停止Swoole RPC服务中...');
        $isRunning = $manager->killProcess(SIGTERM, 15);
        if ($isRunning) {
            $this->output->error('无法停止rpc进程.');
            return;
        }
        $this->output->writeln('> 成功');
    }

    /**
     * 重启server
     * @access protected
     * @param RpcManager $manager
     * @param PidManager $pidManager
     * @return void
     */
    protected function restart(RpcManager $manager, PidManager $pidManager)
    {
        if ($pidManager->isRunning()) {
            $this->stop($pidManager);
        }
        $this->start($manager, $pidManager);
    }

    /**
     * Create swoole server.
     */
    protected function createSwooleServer()
    {
        $config = $this->app->config;
        $host = $config->get('swoole.server.host');
        $port = $config->get('swoole.rpc.server.port');
        $socketType = $config->get('swoole.server.socket_type', SWOOLE_SOCK_TCP);
        $mode = $config->get('swoole.server.mode', SWOOLE_PROCESS);
        /**
         * @var \Swoole\Server $server
         */
        $server = new \Swoole\Server($host, $port, $mode, $socketType);
        $options = $config->get('swoole.server.options');
        $server->set($options);
        return $server;
    }
}