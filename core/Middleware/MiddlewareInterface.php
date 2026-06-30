<?php

declare(strict_types=1);

namespace Kadosys\Core\Middleware;

use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;

/**
 * MiddlewareInterface
 *
 * Contrato que todo middleware da plataforma deve implementar.
 * Segue o padrao "onion": o middleware pode atuar antes de chamar
 * $next($request), depois, ou ambos.
 */
interface MiddlewareInterface
{
    /**
     * @param \Closure(Request): Response $next
     */
    public function handle(Request $request, \Closure $next): Response;
}
