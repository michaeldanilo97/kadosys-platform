<?php

declare(strict_types=1);

namespace Kadosys\Core\Middleware;

use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;
use Kadosys\Core\Security\Auth;

/**
 * GuestMiddleware
 *
 * Garante que apenas visitantes (nao autenticados) acessem a rota.
 * Util para paginas como /login e /register, redirecionando usuarios
 * ja logados direto para o dashboard.
 */
final class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        if ($this->auth->check()) {
            return Response::redirect(url('/dashboard'));
        }

        return $next($request);
    }
}
