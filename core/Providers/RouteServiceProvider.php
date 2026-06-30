<?php

declare(strict_types=1);

namespace Kadosys\Core\Providers;

use Kadosys\Core\Middleware\AuthMiddleware;
use Kadosys\Core\Middleware\GuestMiddleware;
use Kadosys\Core\Middleware\MiddlewarePipeline;
use Kadosys\Core\Middleware\TenantMiddleware;
use Kadosys\Core\Middleware\VerifyCsrfTokenMiddleware;

/**
 * RouteServiceProvider
 *
 * Registra os aliases dos middlewares globais do Core, permitindo
 * que rotas e grupos os referenciem por nome curto (ex: 'auth', 'guest').
 */
final class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        MiddlewarePipeline::registerAlias('auth', AuthMiddleware::class);
        MiddlewarePipeline::registerAlias('guest', GuestMiddleware::class);
        MiddlewarePipeline::registerAlias('csrf', VerifyCsrfTokenMiddleware::class);
        MiddlewarePipeline::registerAlias('tenant', TenantMiddleware::class);
    }
}
