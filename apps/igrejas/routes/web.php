<?php

declare(strict_types=1);

use Igrejas\Controllers\AuthController;
use Igrejas\Controllers\DashboardController;
use Igrejas\Controllers\LandingController;
use Igrejas\Core\Middleware\AuthMiddleware;
use Igrejas\Core\Middleware\GuestMiddleware;

/**
 * Rotas web - KADOSYS Igrejas (Sprint 1).
 *
 * @var \Igrejas\Core\Router $router
 */

// Landing page publica.
$router->get('/', [LandingController::class, 'index']);

// Autenticacao.
$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

$router->get('/esqueci-senha', [AuthController::class, 'showForgotPassword'], [GuestMiddleware::class]);
$router->post('/esqueci-senha', [AuthController::class, 'sendForgotPassword'], [GuestMiddleware::class]);

// Dashboard administrativo (protegido).
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/{slug}', [DashboardController::class, 'page'], [AuthMiddleware::class]);
