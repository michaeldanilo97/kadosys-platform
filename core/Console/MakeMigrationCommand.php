<?php

declare(strict_types=1);

namespace Kadosys\Core\Console;

use Kadosys\Core\Core\Application;

/**
 * MakeMigrationCommand
 *
 * Executa: php kadosys make:migration create_membros_table
 * Gera um novo arquivo de migration em database/migrations/,
 * prefixado com timestamp para garantir ordem de execucao.
 */
final class MakeMigrationCommand implements CommandInterface
{
    public function execute(array $arguments): int
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            echo "Uso: php kadosys make:migration create_tabela_table\n";

            return 1;
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}";
        $className = $this->toClassName($name);

        $path = Application::getInstance()->basePath('database/migrations/' . $fileName . '.php');

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        use Kadosys\\Core\\Database\\Migration;

        final class {$className} extends Migration
        {
            public function up(): void
            {
                \$this->connection()->statement("
                    -- TODO: defina a estrutura da tabela aqui
                ");
            }

            public function down(): void
            {
                \$this->connection()->statement('DROP TABLE IF EXISTS nome_da_tabela');
            }
        }

        PHP;

        file_put_contents($path, $content);

        echo "Migration criada: database/migrations/{$fileName}.php\n";

        return 0;
    }

    private function toClassName(string $name): string
    {
        $parts = explode('_', $name);

        return implode('', array_map('ucfirst', $parts));
    }
}
