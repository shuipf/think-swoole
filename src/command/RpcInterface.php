<?php
// +----------------------------------------------------------------------
// | RpcInterface
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\command;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\PhpFile;
use think\console\Command;
use think\helper\Arr;
use think\swoole\contract\rpc\ParserInterface;
use think\swoole\rpc\client\Gateway;
use think\swoole\rpc\JsonParser;

class RpcInterface extends Command
{
    /**
     * 配置指令
     * @return void
     */
    public function configure()
    {
        $this->setName('swoole:rpcinterface')
            ->setDescription('生成Rpc服务接口');
    }

    /**
     * 执行指令
     * 目的是根据客户端配置获取对应服务端的接口定义，并生成rpc.php文件
     */
    public function handle()
    {
        $clients = $this->app->config->get('swoole.rpc.client', []);
        $file = new PhpFile;
        $file->addComment('This file is auto-generated.');
        $file->setStrictTypes();
        $services = [];
        foreach ($clients as $name => $config) {
            $parserClass = Arr::get($config, 'parser', JsonParser::class);
            /**
             * @var ParserInterface $parser
             */
            $parser = new $parserClass;
            $gateway = new Gateway($config, $parser);
            $result = $gateway->getServices();
            $namespace = $file->addNamespace("rpc\\contract\\${name}");
            foreach ($result as $interface => $methods) {
                $services[$name][] = $namespace->getName() . "\\{$interface}";
                $class = $namespace->addInterface($interface);
                foreach ($methods as $methodName => ['parameters' => $parameters, 'returnType' => $returnType, 'comment' => $comment]) {
                    $method = $class->addMethod($methodName)
                        ->setVisibility(ClassType::VISIBILITY_PUBLIC)
                        ->setComment(Helpers::unformatDocComment($comment))
                        ->setReturnType($returnType);
                    foreach ($parameters as $parameter) {
                        if ($parameter['type'] && (class_exists($parameter['type']) || interface_exists($parameter['type']))) {
                            $namespace->addUse($parameter['type']);
                        }
                        $param = $method->addParameter($parameter['name'])
                            ->setTypeHint($parameter['type']);
                        if (array_key_exists("default", $parameter)) {
                            $param->setDefaultValue($parameter['default']);
                        }
                    }
                }
            }
        }
        $services = "return " . Helpers::dump($services) . ";";
        file_put_contents($this->app->getBasePath() . 'rpc.php', $file . $services);
        $this->output->writeln('<info>Succeed!</info>');
    }
}