<?php
// +----------------------------------------------------------------------
// | Table 内存表
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole;

use Swoole\Table as SwooleTable;

class Table
{

    public const TYPE_INT = 1;

    public const TYPE_STRING = 3;

    public const TYPE_FLOAT = 2;

    /**
     * 已注册的swoole表
     * @var array
     */
    protected $tables = [];

    /**
     * 将swoole表添加到现有表
     * @param string $name
     * @param SwooleTable $table
     * @return Table
     */
    public function add(string $name, SwooleTable $table)
    {
        $this->tables[$name] = $table;
        return $this;
    }

    /**
     * 获取表
     * @param string $name
     * @return SwooleTable $table
     */
    public function get(string $name)
    {
        return $this->tables[$name] ?? null;
    }

    /**
     * 获取全部swoole表
     * @return array
     */
    public function getAll()
    {
        return $this->tables;
    }

    /**
     * 动态访问表
     * @param string $key
     * @return SwooleTable
     */
    public function __get($key)
    {
        return $this->get($key);
    }
}
