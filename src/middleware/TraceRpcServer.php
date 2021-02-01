<?php
// +----------------------------------------------------------------------
// | TraceRpcServer 中间件
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\middleware;

use Swoole\Coroutine;
use think\swoole\rpc\Protocol;
use think\tracing\Tracer;
use Throwable;
use const OpenTracing\Formats\TEXT_MAP;
use const OpenTracing\Tags\ERROR;
use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_SERVER;

class TraceRpcServer
{
    /**
     * @var Tracer
     */
    protected $tracer;

    /**
     * TraceRpcServer constructor.
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
        $context = $this->tracer->extract(TEXT_MAP, $protocol->getContext());
        $scope = $this->tracer->startActiveSpan(
            'rpc.server:' . $protocol->getInterface() . '@' . $protocol->getMethod(),
            [
                'child_of' => $context,
                'tags' => [
                    SPAN_KIND => SPAN_KIND_RPC_SERVER,
                ],
            ]
        );
        $span = $scope->getSpan();

        try {
            return $next($protocol);
        } catch (Throwable $e) {
            $span->setTag(ERROR, $e);
            throw $e;
        } finally {
            $scope->close();

            Coroutine::defer(
                function () {
                    $this->tracer->flush();
                }
            );
        }
    }
}