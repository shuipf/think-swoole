<?php
// +----------------------------------------------------------------------
// | ResetVarDumper 中间件
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\swoole\middleware;

use Closure;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;
use think\Request;

class ResetVarDumper
{
    protected $cloner;

    /**
     * ResetVarDumper constructor.
     */
    public function __construct()
    {
        $this->cloner = new VarCloner();
        $this->cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $prevHandler = VarDumper::setHandler(
            function ($var) {
                $dumper = new HtmlDumper();
                $dumper->dump($this->cloner->cloneVar($var));
            }
        );
        $response = $next($request);
        VarDumper::setHandler($prevHandler);
        return $response;
    }
}