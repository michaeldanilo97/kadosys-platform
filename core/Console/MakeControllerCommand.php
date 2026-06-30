<?php

declare(strict_types=1);

namespace Kadosys\Core\Console;

use Kadosys\Core\Core\Application;

/**
 * MakeControllerCommand
 *
 * Executa: php kadosys make:controller NomeController
 * Gera um novo Controller dentro de apps/{app_ativo}/Controllers/,
 * ja estendendo o Controller base do Core.
 */
final class MakeControllerCommand implements CommandInterface
{
    public function execute(array $arguments): int
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            echo "Uso: php kadosys make:controller NomeController\n";

            return 1;
        }

        $name = str_ends_with($name, 'Controller') ? $name : $name . 'Controller';

        $app = Application::getInstance();
        $activeApp = $app->activeApp();
        $namespace = 'Kadosys\\Apps\\' . ucfirst($activeApp) . '\\Controllers';

        $path = $app->appPath('Controllers/' . $name . '.php');

        if (is_file($path)) {
            echo "Controller '{$name}' ja existe.\n";

            return 1;
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use Kadosys\\Core\\Http\\Controller;
        use Kadosys\\Core\\Http\\Request;
        use Kadosys\\Core\\Http\\Response;

        final class {$name} extends Controller
        {
            public function index(Request \$request): Response
            {
                return \$this->view('dashboard.index');
            }
        }

        PHP;

        file_put_contents($path, $content);

        echo "Controller criado: apps/{$activeApp}/Controllers/{$name}.php\n";

        return 0;
    }
}
