<?php

declare(strict_types=1);

namespace Kadosys\Core\Console;

use Kadosys\Core\Core\Application;
use Kadosys\Core\Database\MigrationRunner;

/**
 * MigrateCommand
 *
 * Executa: php kadosys migrate
 * Aplica todas as migrations pendentes localizadas em database/migrations/.
 */
final class MigrateCommand implements CommandInterface
{
    public function execute(array $arguments): int
    {
        $app = Application::getInstance();
        $runner = new MigrationRunner($app->basePath('database/migrations'));

        echo "Executando migrations...\n";
        $runner->run();
        echo "Concluido.\n";

        return 0;
    }
}
