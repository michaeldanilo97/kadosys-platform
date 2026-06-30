<?php

declare(strict_types=1);

namespace Kadosys\Core\Providers;

use Kadosys\Core\Security\Auth;
use Kadosys\Core\Security\Csrf;

/**
 * AppServiceProvider
 *
 * Registra os servicos transversais da plataforma: Auth e Csrf,
 * disponiveis para qualquer aplicativo via Container.
 */
final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Auth::class, function () {
            return new Auth();
        });

        $this->container->singleton(Csrf::class, function () {
            return new Csrf();
        });
    }
}
