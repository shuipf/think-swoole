<?php
// +----------------------------------------------------------------------
// | InteractsWithSwooleTable 与Swoole Table处理
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\concerns;

use Swoole\Table as SwooleTable;
use think\swoole\Table;

trait InteractsWithSwooleTable
{

    /**
     * 当前表对象
     * @var Table
     */
    protected $currentTable;

    /**
     * Register customized swoole tables.
     */
    protected function prepareTables()
    {
        $this->currentTable = new Table();
        $this->registerTables();
        $this->onEvent(
            'workerStart',
            function () {
                $this->app->instance(Table::class, $this->currentTable);
                foreach ($this->currentTable->getAll() as $name => $table) {
                    $this->app->instance("swoole.table.{$name}", $table);
                }
            }
        );
    }

    /**
     * 创建用户定义的内存表
     * @return void
     */
    protected function registerTables()
    {
        $tables = $this->container->make('config')->get('swoole.tables', []);
        foreach ($tables as $key => $value) {
            $table = new SwooleTable($value['size']);
            $columns = $value['columns'] ?? [];
            foreach ($columns as $column) {
                if (isset($column['size'])) {
                    $table->column($column['name'], $column['type'], $column['size']);
                } else {
                    $table->column($column['name'], $column['type']);
                }
            }
            $table->create();
            $this->currentTable->add($key, $table);
        }
    }

}
