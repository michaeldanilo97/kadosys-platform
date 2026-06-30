<?php

declare(strict_types=1);

namespace Kadosys\Core\Console;

use Kadosys\Core\Core\Application;

/**
 * MakeAppCommand
 *
 * Executa: php kadosys make:app nome_do_app
 * Gera a estrutura padrao de um novo aplicativo dentro de apps/,
 * seguindo exatamente o mesmo layout do aplicativo "igrejas":
 * Controllers, Models, Services, Modules, Views, Routes, Assets,
 * Config e Lang. Nunca cria Composer, Vendor ou qualquer parte
 * do framework dentro do aplicativo — toda infraestrutura
 * permanece exclusivamente no Core.
 */
final class MakeAppCommand implements CommandInterface
{
    /**
     * @var array<int, string>
     */
    private array $directories = [
        'Controllers',
        'Models',
        'Services',
        'Modules',
        'Views/layouts',
        'Routes',
        'Assets/css',
        'Assets/js',
        'Config',
        'Lang/pt_BR',
    ];

    public function execute(array $arguments): int
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            echo "Uso: php kadosys make:app nome_do_app\n";

            return 1;
        }

        $slug = $this->slugify($name);
        $appPath = Application::getInstance()->basePath('apps/' . $slug);

        if (is_dir($appPath)) {
            echo "O aplicativo '{$slug}' ja existe em apps/{$slug}\n";

            return 1;
        }

        foreach ($this->directories as $directory) {
            $fullPath = $appPath . '/' . $directory;

            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0775, true);
            }
        }

        $this->createRoutesFile($appPath, $slug);
        $this->createConfigFile($appPath, $slug);

        echo "Aplicativo '{$slug}' criado com sucesso em apps/{$slug}\n";
        echo "Defina APP_ACTIVE={$slug} no .env para ativa-lo.\n";

        return 0;
    }

    private function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? $slug;

        return trim($slug, '_');
    }

    private function createRoutesFile(string $appPath, string $slug): void
    {
        $content = <<<PHP
        <?php

        declare(strict_types=1);

        /**
         * Rotas do aplicativo "{$slug}".
         *
         * A variavel \$router (instancia de Kadosys\\Core\\Routing\\Router)
         * esta disponivel automaticamente neste arquivo.
         */

        \$router->get('/', function () {
            return view('dashboard.index');
        })->setName('{$slug}.home');

        PHP;

        file_put_contents($appPath . '/Routes/web.php', $content);
    }

    private function createConfigFile(string $appPath, string $slug): void
    {
        $content = <<<PHP
        <?php

        declare(strict_types=1);

        /**
         * Configuracoes especificas do aplicativo "{$slug}".
         * Acessivel via config('{$slug}.chave').
         */

        return [
            'nome' => '{$slug}',
        ];

        PHP;

        file_put_contents($appPath . '/Config/config.php', $content);
    }
}
