<?php

declare(strict_types=1);

namespace Kadosys\Core\Providers;

use Kadosys\Core\Database\Connection;

/**
 * DatabaseServiceProvider
 *
 * Registra a Connection (PDO Singleton) no container. A conexao real
 * com o banco so e estabelecida na primeira resolucao (lazy loading),
 * evitando overhead em rotas que nao acessam o banco.
 */
final class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Connection::class, function () {
            return Connection::getInstance();
        });
    }
}
