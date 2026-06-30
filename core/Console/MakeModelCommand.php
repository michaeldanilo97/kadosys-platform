<?php

declare(strict_types=1);

namespace Kadosys\Core\Console;

use Kadosys\Core\Core\Application;

/**
 * MakeModelCommand
 *
 * Executa: php kadosys make:model NomeModel
 * Gera um novo Model dentro de apps/{app_ativo}/Models/,
 * ja estendendo o Model base do Core (com suporte a tenant_id).
 */
final class MakeModelCommand implements CommandInterface
{
    public function execute(array $arguments): int
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            echo "Uso: php kadosys make:model NomeModel\n";

            return 1;
        }

        $app = Application::getInstance();
        $activeApp = $app->activeApp();
        $namespace = 'Kadosys\\Apps\\' . ucfirst($activeApp) . '\\Models';

        $path = $app->appPath('Models/' . $name . '.php');

        if (is_file($path)) {
            echo "Model '{$name}' ja existe.\n";

            return 1;
        }

        $table = $this->guessTableName($name);

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use Kadosys\\Core\\Database\\Model;

        final class {$name} extends Model
        {
            protected static string \$table = '{$table}';

            protected static bool \$tenantAware = true;
        }

        PHP;

        file_put_contents($path, $content);

        echo "Model criado: apps/{$activeApp}/Models/{$name}.php (tabela: {$table})\n";

        return 0;
    }

    private function guessTableName(string $name): string
    {
        $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name;
        $snake = strtolower($snake);

        return $snake . 's';
    }
}
