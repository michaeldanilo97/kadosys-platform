<?php

declare(strict_types=1);

namespace Kadosys\Core\Middleware;

use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;
use Kadosys\Core\Security\Csrf;

/**
 * VerifyCsrfTokenMiddleware
 *
 * Valida o token CSRF em todas as requisicoes que alteram estado
 * (POST, PUT, PATCH, DELETE), prevenindo ataques Cross-Site Request Forgery.
 */
final class VerifyCsrfTokenMiddleware implements MiddlewareInterface
{
    /**
     * Metodos HTTP que exigem verificacao de CSRF.
     *
     * @var array<int, string>
     */
    private const VERIFIED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private readonly Csrf $csrf)
    {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        if (in_array($request->method(), self::VERIFIED_METHODS, true)) {
            $fieldName = (string) \Kadosys\Core\Core\Config::get('security.csrf_token_name', '_csrf_token');
            $token = (string) $request->input($fieldName, '');

            if (!$this->csrf->isValid($token)) {
                return Response::html('419 - Token CSRF invalido ou expirado.', 419);
            }
        }

        return $next($request);
    }
}
