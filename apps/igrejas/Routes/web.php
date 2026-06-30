<?php

declare(strict_types=1);

/**
 * Rotas do aplicativo "igrejas".
 *
 * A variavel $router (instancia de Kadosys\Core\Routing\Router)
 * esta disponivel automaticamente neste arquivo, carregado pela
 * Application durante o boot.
 *
 * Importante: todas as URIs aqui sao relativas (ex: "/login",
 * "/dashboard"), pois o Request ja remove o APP_BASE_PATH antes
 * do despacho. Nunca utilizar caminhos como "/apps/igrejas/...".
 */

use Kadosys\Apps\Igrejas\Controllers\AuthController;
use Kadosys\Apps\Igrejas\Controllers\DashboardController;

$router->get('/', [DashboardController::class, 'index'])->setName('home');

$router->group(['middleware' => 'guest'], function ($router) {
    $router->get('/login', [AuthController::class, 'showLogin'])->setName('login');
    $router->post('/login', [AuthController::class, 'login'])->middleware('csrf');
});

$router->group(['prefix' => '', 'middleware' => ['auth', 'tenant']], function ($router) {
    $router->get('/dashboard', [DashboardController::class, 'index'])->setName('dashboard');
    $router->post('/logout', [AuthController::class, 'logout'])->middleware('csrf')->setName('logout');
});
