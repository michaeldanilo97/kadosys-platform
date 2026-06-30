<?php

declare(strict_types=1);

namespace Kadosys\Core\Providers;

/**
 * ViewServiceProvider
 *
 * Reservado para configuracoes futuras da camada de View (ex: registro
 * de helpers de template adicionais, compositores de view, etc).
 * Atualmente nao requer registros, pois a View trabalha de forma
 * estatica e sem estado configuravel em runtime.
 */
final class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nenhum binding necessario na v1. Mantido para extensibilidade futura.
    }
}
