<?php
// +----------------------------------------------------------------------
// | TraceRpcClient 中间件
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\middleware;

use think\swoole\rpc\Protocol;
use think\tracing\Tracer;
use Throwable;
use const OpenTracing\Formats\TEXT_MAP;
use const OpenTracing\Tags\ERROR;
use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_CLIENT;

class TraceRpcClient
{
    /**
     * @var Tracer
     */
    protected $tracer;

    /**
     * TraceRpcClient constructor.
     * @param Tracer $tracer
     */
    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    /**
     * @param Protocol $protocol
     * @param $next
     * @return mixed
     * @throws Throwable
     */
    public function handle(Protocol $protocol, $next)
    {
        $scope = $this->tracer->startActiveSpan(
            'rpc.client:' . $protocol->getInterface() . '@' . $protocol->getMethod(),
            [
                'tags' => [
                    SPAN_KIND => SPAN_KIND_RPC_CLIENT,
                ],
            ]
        );
        $span = $scope->getSpan();
        $context = $protocol->getContext();
        $this->tracer->inject($span->getContext(), TEXT_MAP, $context);
        $protocol->setContext($context);
        try {
            return $next($protocol);
        } catch (Throwable $e) {
            $span->setTag(ERROR, $e);
            throw $e;
        } finally {
            $scope->close();
        }
    }
}