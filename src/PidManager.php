<?php
// +----------------------------------------------------------------------
// | PidManager
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use RuntimeException;
use Swoole\Process;
use think\helper\Arr;

class PidManager
{
    /**
     * @var string
     */
    protected $file;

    /**
     * PidManager constructor.
     * @param string|null $file
     */
    public function __construct(string $file = null)
    {
        $this->file = $file ?? (sys_get_temp_dir() . '/swoole.pid');
    }

    /**
     * 创建swoole.pid文件并记录master和manager进程ID
     * @param int $masterPid 服务器主进程的PID
     * @param int $managerPid 管理进程的的PID
     */
    public function create(int $masterPid, int $managerPid)
    {
        if (!is_writable($this->file)
            && !is_writable(dirname($this->file))
        ) {
            throw new RuntimeException(
                sprintf('Pid file "%s" is not writable', $this->file)
            );
        }
        file_put_contents($this->file, $masterPid . ',' . $managerPid);
    }

    /**
     * 获取主进程PID
     * @return int
     */
    public function getMasterPid()
    {
        return $this->getPids()['masterPid'];
    }

    /**
     * 获取管理进程PID
     * @return int
     */
    public function getManagerPid()
    {
        return $this->getPids()['managerPid'];
    }

    /**
     * 分析swoole.pid文件，并返回主进程，管理进程PID数组
     * @return array
     */
    public function getPids(): array
    {
        $pids = [];
        if (is_readable($this->file)) {
            $content = file_get_contents($this->file);
            $pids = explode(',', $content);
        }
        return [
            'masterPid' => $pids[0] ?? null,
            'managerPid' => $pids[1] ?? null,
        ];
    }

    /**
     * 是否运行中
     * @return bool
     */
    public function isRunning()
    {
        $pids = $this->getPids();
        if (!count($pids)) {
            return false;
        }
        $masterPid = $pids['masterPid'] ?? null;
        $managerPid = $pids['managerPid'] ?? null;
        if ($managerPid) {
            // Swoole process mode
            return $masterPid && $managerPid && Process::kill((int)$managerPid, 0);
        }
        // Swoole base mode, no manager process
        return $masterPid && Process::kill((int)$masterPid, 0);
    }

    /**
     * Kill process.
     * @param int $sig
     * @param int $wait
     * @return bool
     */
    public function killProcess($sig, $wait = 0)
    {
        Process::kill(
            Arr::first($this->getPids()),
            $sig
        );
        if ($wait) {
            $start = time();
            do {
                if (!$this->isRunning()) {
                    break;
                }
                usleep(100000);
            } while (time() < $start + $wait);
        }
        return $this->isRunning();
    }

    /**
     * 移除pid文件
     * @return bool
     */
    public function remove(): bool
    {
        if (is_writable($this->file)) {
            return unlink($this->file);
        }
        return false;
    }
}