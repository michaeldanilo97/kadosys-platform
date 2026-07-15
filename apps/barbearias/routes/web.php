<?php

declare(strict_types=1);

use Barbearias\Controllers\AssinaturaController;
use Barbearias\Controllers\AuthController;
use Barbearias\Controllers\CadastroController;
use Barbearias\Controllers\DashboardController;
use Barbearias\Controllers\LandingController;
use Barbearias\Controllers\WebhookController;
use Barbearias\Core\Middleware\AuthMiddleware;
use Barbearias\Core\Middleware\GuestMiddleware;

/** @var \Barbearias\Core\Router $router */

// Pagina publica de vendas.
$router->get('/', [LandingController::class, 'index']);

// Cadastro publico (barbearia + admin + plano + pagamento).
$router->get('/cadastro', [CadastroController::class, 'form'], [GuestMiddleware::class]);
$router->post('/cadastro', [CadastroController::class, 'enviar'], [GuestMiddleware::class]);
$router->get('/cadastro/pix/{id}', [CadastroController::class, 'pix']);
$router->get('/cadastro/pix/{id}/status', [CadastroController::class, 'pixStatus']);
$router->get('/cadastro/retorno', [CadastroController::class, 'retorno']);

// Notificacoes assincronas do Mercado Pago (confirmacao de pagamento).
$router->post('/webhooks/mercadopago', [WebhookController::class, 'mercadoPago']);

// Autenticacao.
$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

// Dashboard administrativo (protegido).
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Tela de assinatura pendente (trial vencido / fatura Pix vencida / pagamento aguardando confirmacao).
$router->get('/dashboard/assinatura', [AssinaturaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/assinatura/status', [AssinaturaController::class, 'status'], [AuthMiddleware::class]);
$router->post('/dashboard/assinatura/pix', [AssinaturaController::class, 'gerarPix'], [AuthMiddleware::class]);
$router->post('/dashboard/assinatura/cartao', [AssinaturaController::class, 'assinarCartao'], [AuthMiddleware::class]);
