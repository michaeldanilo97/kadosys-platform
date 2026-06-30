<?php

declare(strict_types=1);

namespace Kadosys\Core\Middleware;

use Kadosys\Core\Core\Config;
use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;
use Kadosys\Core\Security\Auth;
use Kadosys\Core\Support\Tenant;

/**
 * TenantMiddleware
 *
 * Resolve o tenant (igreja/empresa/organizacao) atual com base no
 * usuario autenticado e disponibiliza essa informacao globalmente
 * via Tenant::current(), para que Models filtrem dados automaticamente
 * por tenant_id.
 */
final class TenantMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        if (!Config::get('tenant.enabled', true)) {
            return $next($request);
        }

        $tenantId = null;

        if ($this->auth->check()) {
            $user = $this->auth->user();
            $tenantId = is_array($user) ? ($user['tenant_id'] ?? null) : null;
        }

        Tenant::setCurrent($tenantId ?? (int) Config::get('tenant.default_id', 1));

        return $next($request);
    }
}
