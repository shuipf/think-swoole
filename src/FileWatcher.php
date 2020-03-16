<?php
// +----------------------------------------------------------------------
// | FileWatcher 文件监控
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FileWatcher
{
    /**
     * @var Finder
     */
    protected $finder;

    /**
     * @var array
     */
    protected $files = [];

    /**
     * FileWatcher constructor.
     * @param string $directory 定义监听目录或者文件
     * @param string $exclude 排除目录
     * @param string $name 匹配的文件后缀
     */
    public function __construct($directory, $exclude, $name)
    {
        $this->finder = new Finder();
        $this->finder->files()
            ->name($name)
            ->in($directory)
            ->exclude($exclude);
    }

    /**
     * 获取文件上次修改时间数组
     * @return array
     */
    protected function findFiles()
    {
        $files = [];
        /** @var SplFileInfo $f */
        foreach ($this->finder as $f) {
            $files[$f->getRealpath()] = $f->getMTime();
        }
        return $files;
    }

    /**
     * 启动一个定时任务进行监控
     * @param callable $callback 闭包回调
     */
    public function watch(callable $callback)
    {
        $this->files = $this->findFiles();

        //添加一个定时器监控文件修改情况
        swoole_timer_tick(
            1000,
            function () use ($callback) {

                $files = $this->findFiles();

                foreach ($files as $path => $time) {
                    if (empty($this->files[$path]) || $this->files[$path] != $time) {
                        call_user_func($callback);
                        break;
                    }
                }

                $this->files = $files;
            }
        );
    }
}
