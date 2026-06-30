<?php

declare(strict_types=1);

namespace Kadosys\Core\Middleware;

use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;
use Kadosys\Core\Security\Auth;

/**
 * AuthMiddleware
 *
 * Garante que apenas usuarios autenticados acessem a rota.
 * Caso o usuario nao esteja logado, redireciona para a rota de login.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        if (!$this->auth->check()) {
            return Response::redirect(url('/login'));
        }

        return $next($request);
    }
}
