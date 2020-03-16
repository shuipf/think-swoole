<?php
// +----------------------------------------------------------------------
// | ModifyProperty 通过反射的方式设置受保护的属性值
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\concerns;

use ReflectionObject;

trait ModifyProperty
{
    /**
     * 通过反射的方式设置受保护的属性值
     * @param object $object 对象
     * @param string|object $value 对象值
     * @param string $property 属性名称
     * @throws \ReflectionException
     */
    protected function modifyProperty($object, $value, $property = 'app')
    {
        //获取对象详情
        $reflectObject = new ReflectionObject($object);
        //判断属性是否存在
        if ($reflectObject->hasProperty($property)) {
            //获取属性信息
            $reflectProperty = $reflectObject->getProperty($property);
            //设置属性可访问性
            $reflectProperty->setAccessible(true);
            //设置属性值
            $reflectProperty->setValue($object, $value);
        }
    }
}