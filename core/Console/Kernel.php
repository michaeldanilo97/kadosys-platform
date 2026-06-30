<?php

declare(strict_types=1);

namespace Kadosys\Core\Console;

/**
 * Kernel
 *
 * Dispatcher simples de comandos CLI da plataforma, executado via
 * "php kadosys <comando> [argumentos]" a partir da raiz do projeto.
 */
final class Kernel
{
    /**
     * @var array<string, class-string>
     */
    private array $commands = [
        'migrate' => MigrateCommand::class,
        'make:app' => MakeAppCommand::class,
        'make:controller' => MakeControllerCommand::class,
        'make:model' => MakeModelCommand::class,
        'make:migration' => MakeMigrationCommand::class,
    ];

    /**
     * @param array<int, string> $argv
     */
    public function handle(array $argv): int
    {
        $command = $argv[1] ?? null;
        $arguments = array_slice($argv, 2);

        if ($command === null || !isset($this->commands[$command])) {
            $this->printUsage();

            return 1;
        }

        $commandClass = $this->commands[$command];
        $instance = new $commandClass();

        return $instance->execute($arguments);
    }

    private function printUsage(): void
    {
        echo "Kadosys Platform CLI\n\n";
        echo "Comandos disponiveis:\n";

        foreach (array_keys($this->commands) as $name) {
            echo "  php kadosys {$name}\n";
        }
    }
}
