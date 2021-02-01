<?php
// +----------------------------------------------------------------------
// | Dispatcher
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\rpc\server;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use Swoole\Server;
use think\App;
use think\swoole\contract\rpc\ParserInterface;
use think\swoole\Middleware;
use think\swoole\rpc\Error;
use think\swoole\rpc\File;
use think\swoole\rpc\Packer;
use think\swoole\rpc\Protocol;
use Throwable;

class Dispatcher
{

    /**
     * Parser error
     */
    const PARSER_ERROR = -32700;

    /**
     * Invalid Request
     */
    const INVALID_REQUEST = -32600;

    /**
     * Method not found
     */
    const METHOD_NOT_FOUND = -32601;

    /**
     * Invalid params
     */
    const INVALID_PARAMS = -32602;

    /**
     * Internal error
     */
    const INTERNAL_ERROR = -32603;

    /**
     * @var App
     */
    protected $app;

    /**
     * 数据解析对象
     * @var ParserInterface
     */
    protected $parser;

    /**
     * 接口列表
     * @var array
     */
    protected $services = [];

    /**
     * @var Server
     */
    protected $server;

    /**
     * @var array
     */
    protected $files = [];

    /**
     * @var array|mixed
     */
    protected $middleware = [];

    /**
     * Dispatcher constructor.
     * @param App $app
     * @param ParserInterface $parser
     * @param Server $server
     * @param $services
     * @throws \ReflectionException
     */
    public function __construct(App $app, ParserInterface $parser, Server $server, $services, $middleware = [])
    {
        $this->app = $app;
        $this->parser = $parser;
        $this->server = $server;
        $this->prepareServices($services);
        $this->middleware = $middleware;
    }

    /**
     * 调度
     * @param int $fd
     * @param string|File|Error $data
     */
    public function dispatch(int $fd, $data)
    {
        try {
            switch (true) {
                //文件
                case $data instanceof File:
                    $this->files[$fd][] = $data;
                    return;
                //错误信息
                case $data instanceof Error:
                    $result = $data;
                    break;
                //获取接口信息，用于rpc.php文件生成
                case $data === Protocol::ACTION_INTERFACE:
                    $result = $this->getInterfaces();
                    break;
                default:
                    $protocol = $this->parser->decode($data);
                    $result = $this->dispatchWithMiddleware($protocol, $fd);
            }
        } catch (Throwable $e) {
            $result = Error::make($e->getCode(), $e->getMessage());
        }
        $data = $this->parser->encodeResponse($result);
        $this->server->send($fd, Packer::pack($data));
        //清空文件缓存
        unset($this->files[$fd]);
    }

    /**
     * 中间件调度
     * @param Protocol $protocol
     * @param $fd
     * @return mixed
     */
    protected function dispatchWithMiddleware(Protocol $protocol, $fd)
    {
        return Middleware::make($this->app, $this->middleware)
            ->pipeline()
            ->send($protocol)
            ->then(
                function (Protocol $protocol) use ($fd) {
                    $interface = $protocol->getInterface();
                    $method = $protocol->getMethod();
                    $params = $protocol->getParams();
                    //文件参数
                    foreach ($params as $index => $param) {
                        if ($param === Protocol::FILE) {
                            $params[$index] = array_shift($this->files[$fd]);
                        }
                    }
                    $service = $this->services[$interface] ?? null;
                    if (empty($service)) {
                        throw new RuntimeException(
                            sprintf('Service %s is not founded!', $interface),
                            self::METHOD_NOT_FOUND
                        );
                    }
                    return $this->app->invoke([$this->app->make($service['class']), $method], $params);
                }
            );
    }

    /**
     * 获取服务接口
     * @param $services
     * @throws ReflectionException
     */
    protected function prepareServices($services)
    {
        foreach ($services as $className) {
            $reflectionClass = new ReflectionClass($className);
            $interfaces = $reflectionClass->getInterfaceNames();
            foreach ($interfaces as $interface) {
                $this->services[class_basename($interface)] = [
                    'interface' => $interface,
                    'class' => $className,
                ];
            }
        }
    }

    /**
     * 获取接口信息
     * @return array
     * @throws \ReflectionException
     */
    protected function getInterfaces()
    {
        $interfaces = [];
        foreach ($this->services as $key => ['interface' => $interface]) {
            $interfaces[$key] = $this->getMethods($interface);
        }
        return $interfaces;
    }

    /**
     * 通过反射的方式获取方法列表
     * @param $interface
     * @return array
     * @throws \ReflectionException
     */
    protected function getMethods($interface)
    {
        $methods = [];
        $reflection = new ReflectionClass($interface);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();
            if ($returnType instanceof ReflectionNamedType) {
                $returnType = $returnType->getName();
            }
            $methods[$method->getName()] = [
                'parameters' => $this->getParameters($method),
                'returnType' => $returnType,
                'comment' => $method->getDocComment(),
            ];
        }
        return $methods;
    }

    /**
     * 通过反射的方式获取参数定义
     * @param ReflectionMethod $method
     * @return array
     * @throws \ReflectionException
     */
    protected function getParameters(ReflectionMethod $method)
    {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType) {
                $type = $type->getName();
            }
            $param = [
                'name' => $parameter->getName(),
                'type' => $type,
            ];
            if ($parameter->isOptional()) {
                $param['default'] = $parameter->getDefaultValue();
            }
            $parameters[] = $param;
        }
        return $parameters;
    }

}
