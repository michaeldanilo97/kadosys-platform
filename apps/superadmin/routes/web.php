<?php

declare(strict_types=1);

use Superadmin\Controllers\AuthController;
use Superadmin\Controllers\SiteController;
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

$router->get('/sites', [SiteController::class, 'index'], [AuthMiddleware::class]);
$router->post('/sites/{produto}/{id}/suspender', [SiteController::class, 'suspender'], [AuthMiddleware::class]);
$router->post('/sites/{produto}/{id}/reativar', [SiteController::class, 'reativar'], [AuthMiddleware::class]);
$router->get('/sites/{produto}/{id}/excluir', [SiteController::class, 'confirmarExclusao'], [AuthMiddleware::class]);
$router->post('/sites/{produto}/{id}/excluir', [SiteController::class, 'excluir'], [AuthMiddleware::class]);

// As rotas /avisos (envio de avisos) sao adicionadas aqui quando o
// modulo e implementado - protegidas por AuthMiddleware.
