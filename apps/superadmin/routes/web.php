<?php

declare(strict_types=1);

use Superadmin\Controllers\AuthController;
use Superadmin\Core\Middleware\AuthMiddleware;

/** @var \Superadmin\Core\Router $router */
/** @var array $config */

$router->get('/', static function () use ($config): void {
    header('Location: ' . ($config['base_path'] ?? '') . '/sites');
    exit;
});

$router->get('/entrar', [AuthController::class, 'entrar']);
$router->post('/entrar', [AuthController::class, 'autenticar']);
$router->post('/sair', [AuthController::class, 'sair']);

// As rotas /sites e /avisos (listagem, suspender/reativar/excluir, envio
// de avisos) sao adicionadas aqui conforme cada modulo e implementado -
// todas protegidas por AuthMiddleware.
