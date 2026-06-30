<?php

declare(strict_types=1);

namespace Kadosys\Core\Database;

/**
 * MigrationRunner
 *
 * Responsavel por descobrir, executar e registrar o historico de
 * migrations da plataforma, garantindo que cada migration seja
 * executada apenas uma vez (tabela kadosys_migrations).
 */
final class MigrationRunner
{
    private Connection $connection;

    public function __construct(private readonly string $migrationsPath)
    {
        $this->connection = Connection::getInstance();
        $this->ensureMigrationsTableExists();
    }

    private function ensureMigrationsTableExists(): void
    {
        $this->connection->statement('
            CREATE TABLE IF NOT EXISTS kadosys_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at DATETIME NOT NULL
            )
        ');
    }

    /**
     * Executa todas as migrations pendentes encontradas no diretorio configurado.
     */
    public function run(): void
    {
        $executed = $this->getExecutedMigrations();
        $files = glob($this->migrationsPath . '/*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $executed, true)) {
                continue;
            }

            require_once $file;

            $className = $this->resolveClassName($name);

            if (!class_exists($className)) {
                continue;
            }

            /** @var Migration $migration */
            $migration = new $className();
            $migration->up();

            $this->markAsExecuted($name);

            echo "Migration executada: {$name}\n";
        }
    }

    /**
     * @return array<int, string>
     */
    private function getExecutedMigrations(): array
    {
        $rows = $this->connection->statement('SELECT migration FROM kadosys_migrations')->fetchAll();

        return array_column($rows, 'migration');
    }

    private function markAsExecuted(string $name): void
    {
        $this->connection->statement(
            'INSERT INTO kadosys_migrations (migration, executed_at) VALUES (:migration, :executed_at)',
            [':migration' => $name, ':executed_at' => date('Y-m-d H:i:s')]
        );
    }

    /**
     * Converte o nome do arquivo (snake_case com timestamp) em PascalCase
     * para resolver o nome da classe da migration.
     * Exemplo: 2025_01_01_000000_create_users_table -> CreateUsersTable
     */
    private function resolveClassName(string $fileName): string
    {
        $withoutTimestamp = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $fileName) ?? $fileName;
        $parts = explode('_', $withoutTimestamp);
        $className = implode('', array_map('ucfirst', $parts));

        return $className;
    }
}
