<?php

declare(strict_types=1);

use Academias\Controllers\AssinaturaController;
use Academias\Controllers\AuthController;
use Academias\Controllers\CadastroController;
use Academias\Controllers\DashboardController;
use Academias\Controllers\FaturaController;
use Academias\Controllers\LandingController;
use Academias\Controllers\WebhookController;
use Academias\Core\Middleware\AuthMiddleware;
use Academias\Core\Middleware\GuestMiddleware;

/** @var \Academias\Core\Router $router */

// Pagina publica de vendas.
$router->get('/', [LandingController::class, 'index']);

// Cadastro publico (academia + admin + plano + pagamento).
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
$router->get('/esqueci-senha', [AuthController::class, 'showForgotPassword'], [GuestMiddleware::class]);
$router->post('/esqueci-senha', [AuthController::class, 'sendForgotPassword'], [GuestMiddleware::class]);
$router->get('/redefinir-senha/{token}', [AuthController::class, 'showResetPassword'], [GuestMiddleware::class]);
$router->post('/redefinir-senha/{token}', [AuthController::class, 'resetPassword'], [GuestMiddleware::class]);

// Dashboard administrativo (protegido).
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Tela de assinatura pendente (trial vencido / fatura Pix vencida / pagamento aguardando confirmacao).
$router->get('/dashboard/assinatura', [AssinaturaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/assinatura/status', [AssinaturaController::class, 'status'], [AuthMiddleware::class]);
$router->post('/dashboard/assinatura/pix', [AssinaturaController::class, 'gerarPix'], [AuthMiddleware::class]);
$router->post('/dashboard/assinatura/cartao', [AssinaturaController::class, 'assinarCartao'], [AuthMiddleware::class]);

// Faturas (historico de cobranca - sempre acessivel, mesmo bloqueado).
$router->get('/dashboard/faturas', [FaturaController::class, 'index'], [AuthMiddleware::class]);
