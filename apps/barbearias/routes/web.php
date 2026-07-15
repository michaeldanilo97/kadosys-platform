<?php

declare(strict_types=1);

use Barbearias\Controllers\AuthController;
use Barbearias\Controllers\DashboardController;
use Barbearias\Core\Middleware\AuthMiddleware;
use Barbearias\Core\Middleware\GuestMiddleware;

/** @var \Barbearias\Core\Router $router */

// Autenticacao.
$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

// Redireciona a raiz pro login (nao ha landing page publica ainda).
$router->get('/', [AuthController::class, 'home']);

// Dashboard administrativo (protegido).
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
