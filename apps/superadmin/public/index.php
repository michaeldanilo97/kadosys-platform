<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Front Controller - KADOSYS Super Admin
|--------------------------------------------------------------------------
|
| Ponto de entrada unico da aplicacao. Todas as requisicoes sao
| reescritas para este arquivo via public/.htaccess e despachadas pelo
| Router de acordo com routes/web.php.
|
*/

use Superadmin\Core\Request;
use Superadmin\Core\Router;
use Superadmin\Core\Session;
use Superadmin\Core\View;

$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($vendorAutoload)) {
    require $vendorAutoload;
} else {
    // Fallback simples enquanto "composer install" nao e executado no
    // servidor. O Composer continua sendo a forma oficial de gerar o
    // autoload (psr-4, ver composer.json); este bloco apenas evita que a
    // aplicacao quebre antes do deploy completo.
    spl_autoload_register(static function (string $class): void {
        $prefixes = [
            'Superadmin\\Core\\' => dirname(__DIR__) . '/src/Core/',
            'Superadmin\\Controllers\\' => dirname(__DIR__) . '/src/Controllers/',
            'Superadmin\\Models\\' => dirname(__DIR__) . '/src/Models/',
        ];

        foreach ($prefixes as $prefix => $dir) {
            if (str_starts_with($class, $prefix)) {
                $relative = substr($class, strlen($prefix));
                $file = $dir . str_replace('\\', '/', $relative) . '.php';

                if (is_file($file)) {
                    require $file;
                }

                return;
            }
        }
    });
}

$config = require dirname(__DIR__) . '/config/config.php';
date_default_timezone_set($config['timezone']);

if ($config['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

Session::start($config['session_name']);
View::setBasePath(dirname(__DIR__) . '/resources/views');

$request = Request::capture();

$router = new Router($request, $config['base_path'], $config);
require dirname(__DIR__) . '/routes/web.php';
$router->dispatch();
