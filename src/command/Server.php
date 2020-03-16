<?php
// +----------------------------------------------------------------------
// | Server
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\command;

use Swoole\Http\Server as HttpServer;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\swoole\Manager;
use think\swoole\PidManager;

class Server extends Command
{

    /**
     * 配置指令
     * @return void
     */
    public function configure()
    {
        $this->setName('swoole')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload", 'start')
            ->setDescription('启动 Swoole 服务');
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
     * @throws \Exception
     */
    public function handle()
    {
        //检查环境
        $this->checkEnvironment();
        $action = $this->input->getArgument('action');
        if (in_array($action, ['start', 'stop', 'reload', 'restart'])) {
            $this->app->invokeMethod([$this, $action], [], true);
        } else {
            $this->output->writeln("<error>参数操作无效:{$action}, 支持 start|stop|restart|reload .</error>");
        }
    }

    /**
     * 创建服务
     * Create swoole server.
     */
    protected function createSwooleServer()
    {
        $config = $this->app->config;
        $host = $config->get('swoole.server.host');
        $port = $config->get('swoole.server.port');
        $socketType = $config->get('swoole.server.socket_type', SWOOLE_SOCK_TCP);
        $mode = $config->get('swoole.server.mode', SWOOLE_PROCESS);
        /**
         * @var \Swoole\Server $server
         */
        $server = new HttpServer($host, $port, $mode, $socketType);
        $options = $config->get('swoole.server.options');
        $server->set($options);
        return $server;
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
     * 启动服务
     * @param Manager $manager
     * @param PidManager $pidManager
     * @return void
     */
    protected function start(Manager $manager, PidManager $pidManager)
    {
        if ($pidManager->isRunning()) {
            $this->output->writeln('<error>Swoole Http服务器进程已经运行.</error>');
            return;
        }
        $this->output->writeln('启动Swoole Http服务器...');

        $host = $host = $manager->getConfig('server.host');
        $port = $manager->getConfig('server.port');

        $this->output->writeln("Swoole Http服务器已启动: <http://{$host}:{$port}>");
        $this->output->writeln('退出可以按 <info>`CTRL-C`</info>');

        $manager->run();
    }

    /**
     * 柔性重启服务
     * @param PidManager $manager
     * @return void
     */
    protected function reload(PidManager $manager)
    {
        if (!$manager->isRunning()) {
            $this->output->writeln('<error>没有Swoole Http服务在运行</error>');
            return;
        }
        $this->output->writeln('重新加载Swoole Http服务中...');
        //https://wiki.swoole.com/wiki/page/p-server/reload.html
        //SIGUSR1: 向主进程/管理进程发送SIGUSR1信号，将平稳地重启所有Worker进程
        if (!$manager->killProcess(SIGUSR1)) {
            $this->output->error('> 失败');
            return;
        }
        $this->output->writeln('> 成功');
    }

    /**
     * 停止服务
     * @param PidManager $manager
     * @return void
     */
    protected function stop(PidManager $manager)
    {
        if (!$manager->isRunning()) {
            $this->output->writeln('<error>没有Swoole Http服务在运行</error>');
            return;
        }
        //https://wiki.swoole.com/wiki/page/p-server/reload.html
        //SIGTERM: 向主进程/管理进程发送此信号服务器将安全终止
        $this->output->writeln('停止Swoole Http服务中...');
        $isRunning = $manager->killProcess(SIGTERM, 15);
        if ($isRunning) {
            $this->output->error('无法停止swoole_http_server进程');
            return;
        }
        $this->output->writeln('> 成功');
    }

    /**
     * 重启服务
     * @param Manager $manager
     * @param PidManager $pidManager
     * @return void
     */
    protected function restart(Manager $manager, PidManager $pidManager)
    {
        if ($pidManager->isRunning()) {
            $this->stop($pidManager);
        }
        $this->start($manager, $pidManager);
    }
}
