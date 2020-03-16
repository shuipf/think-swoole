<?php
// +----------------------------------------------------------------------
// | Context 协程间的数据传递
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\coroutine;

use Closure;
use Swoole\Coroutine;

class Context
{

    /**
     * 不同协同环境下的数据
     * @var array
     */
    protected static $data = [];

    /**
     * 获取协程设置的数据
     * @param string $key
     * @param null $default 默认值
     * @return mixed|null
     */
    public static function getData(string $key, $default = null)
    {
        return static::$data[static::getCoroutineId()][$key] ?? $default;
    }

    /**
     * 判断数据是否存在
     * @param string $key
     * @return bool
     */
    public static function hasData(string $key)
    {
        return isset(static::$data[static::getCoroutineId()]) && array_key_exists($key, static::$data[static::getCoroutineId()]);
    }

    /**
     * @param string $key
     * @param $value
     * @return mixed|null
     */
    public static function rememberData(string $key, $value)
    {
        if (self::hasData($key)) {
            return self::getData($key);
        }
        if ($value instanceof Closure) {
            // 获取缓存数据
            $value = $value();
        }
        self::setData($key, $value);
        return $value;
    }

    /**
     * 设置当前协程中的数据
     * @param string $key
     * @param $value
     */
    public static function setData(string $key, $value)
    {
        static::$data[static::getCoroutineId()][$key] = $value;
    }

    /**
     * 移除当前协程中的数据
     * @param string $key
     * @return void
     */
    public static function removeData(string $key)
    {
        unset(static::$data[static::getCoroutineId()][$key]);
    }

    /**
     * 获取当前协程数据
     * @return array
     */
    public static function getDataKeys()
    {
        return array_keys(static::$data[static::getCoroutineId()] ?? []);
    }

    /**
     * 清理数据
     * @return void
     */
    public static function clear()
    {
        unset(static::$data[static::getCoroutineId()]);
    }

    /**
     * 获取协程ID
     * @return int
     */
    public static function getCoroutineId()
    {
        return Coroutine::getCid();
    }
}
