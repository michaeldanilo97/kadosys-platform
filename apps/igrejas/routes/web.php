<?php

declare(strict_types=1);

use Igrejas\Controllers\AuthController;
use Igrejas\Controllers\CultoController;
use Igrejas\Controllers\DashboardController;
use Igrejas\Controllers\LandingController;
use Igrejas\Controllers\MembroController;
use Igrejas\Controllers\MinisterioController;
use Igrejas\Core\Middleware\AuthMiddleware;
use Igrejas\Core\Middleware\GuestMiddleware;

/**
 * Rotas web - KADOSYS Igrejas.
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

// Modulo Membros. Registradas antes do catch-all de modulo (abaixo) para
// que "/dashboard/membros" seja resolvida pelo MembroController e nao pela
// pagina generica "em construcao".
$router->get('/dashboard/membros', [MembroController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/membros/novo', [MembroController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/membros', [MembroController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/membros/{id}/editar', [MembroController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/membros/{id}', [MembroController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/membros/{id}/excluir', [MembroController::class, 'destroy'], [AuthMiddleware::class]);

// Modulo Ministerios. Mesmo motivo: precisam vir antes do catch-all.
$router->get('/dashboard/ministerios', [MinisterioController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/ministerios/novo', [MinisterioController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/ministerios', [MinisterioController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/ministerios/{id}/editar', [MinisterioController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/ministerios/{id}', [MinisterioController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/ministerios/{id}/excluir', [MinisterioController::class, 'destroy'], [AuthMiddleware::class]);
$router->post('/dashboard/ministerios/{id}/voluntarios', [MinisterioController::class, 'addVoluntario'], [AuthMiddleware::class]);
$router->post('/dashboard/ministerios/{id}/voluntarios/{membroId}/remover', [MinisterioController::class, 'removeVoluntario'], [AuthMiddleware::class]);

// Modulo Cultos. Mesmo motivo: precisam vir antes do catch-all.
$router->get('/dashboard/cultos', [CultoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/cultos/novo', [CultoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos', [CultoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/cultos/{id}/editar', [CultoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}', [CultoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}/excluir', [CultoController::class, 'destroy'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}/presencas', [CultoController::class, 'addPresenca'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}/presencas/{membroId}/remover', [CultoController::class, 'removePresenca'], [AuthMiddleware::class]);

// Estrutura "em construcao" dos demais modulos do menu (catch-all).
$router->get('/dashboard/{slug}', [DashboardController::class, 'page'], [AuthMiddleware::class]);
