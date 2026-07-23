<?php

declare(strict_types=1);

use Food\Controllers\AssinaturaController;
use Food\Controllers\AuthController;
use Food\Controllers\CadastroController;
use Food\Controllers\CategoriaController;
use Food\Controllers\DashboardController;
use Food\Controllers\FaturaController;
use Food\Controllers\FornecedorController;
use Food\Controllers\IngredienteController;
use Food\Controllers\LandingController;
use Food\Controllers\ProdutoController;
use Food\Controllers\WebhookController;
use Food\Core\Middleware\AuthMiddleware;
use Food\Core\Middleware\GuestMiddleware;

/** @var \Food\Core\Router $router */

// Pagina publica de vendas.
$router->get('/', [LandingController::class, 'index']);

// Cadastro publico (restaurante + admin + plano + pagamento).
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

// Categorias de produto (Doces, Bolos, Salgados, etc).
$router->get('/dashboard/categorias', [CategoriaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/categorias/nova', [CategoriaController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/categorias', [CategoriaController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/categorias/{id}/editar', [CategoriaController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/categorias/{id}', [CategoriaController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/categorias/{id}/excluir', [CategoriaController::class, 'destroy'], [AuthMiddleware::class]);

// Ingredientes (base da Ficha Tecnica).
$router->get('/dashboard/ingredientes', [IngredienteController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/ingredientes/novo', [IngredienteController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/ingredientes', [IngredienteController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/ingredientes/{id}/editar', [IngredienteController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/ingredientes/{id}', [IngredienteController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/ingredientes/{id}/excluir', [IngredienteController::class, 'destroy'], [AuthMiddleware::class]);

// Fornecedores.
$router->get('/dashboard/fornecedores', [FornecedorController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/fornecedores/novo', [FornecedorController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/fornecedores', [FornecedorController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/fornecedores/{id}/editar', [FornecedorController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/fornecedores/{id}', [FornecedorController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/fornecedores/{id}/excluir', [FornecedorController::class, 'destroy'], [AuthMiddleware::class]);

// Produtos + Ficha Tecnica (custeio automatico via Food\Core\Custeio).
$router->get('/dashboard/produtos', [ProdutoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/produtos/novo', [ProdutoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos', [ProdutoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/produtos/{id}/editar', [ProdutoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos/{id}', [ProdutoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos/{id}/excluir', [ProdutoController::class, 'destroy'], [AuthMiddleware::class]);
$router->get('/dashboard/produtos/{id}/ficha-tecnica', [ProdutoController::class, 'fichaTecnica'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos/{id}/ficha-tecnica', [ProdutoController::class, 'fichaTecnicaAdicionar'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos/{id}/ficha-tecnica/{itemId}/excluir', [ProdutoController::class, 'fichaTecnicaRemover'], [AuthMiddleware::class]);

// Faturas (historico de cobranca - sempre acessivel, mesmo bloqueado).
$router->get('/dashboard/faturas', [FaturaController::class, 'index'], [AuthMiddleware::class]);
