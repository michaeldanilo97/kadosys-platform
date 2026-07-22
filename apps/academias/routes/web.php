<?php

declare(strict_types=1);

use Academias\Controllers\AlunoController;
use Academias\Controllers\AssinaturaController;
use Academias\Controllers\AuthController;
use Academias\Controllers\CadastroController;
use Academias\Controllers\ConfiguracaoController;
use Academias\Controllers\DashboardController;
use Academias\Controllers\FaturaController;
use Academias\Controllers\FinanceiroController;
use Academias\Controllers\LandingController;
use Academias\Controllers\PlanoMatriculaController;
use Academias\Controllers\ProfessorController;
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

// Alunos.
$router->get('/dashboard/alunos', [AlunoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/alunos/novo', [AlunoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/alunos', [AlunoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/alunos/{id}/editar', [AlunoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/alunos/{id}', [AlunoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/alunos/{id}/excluir', [AlunoController::class, 'destroy'], [AuthMiddleware::class]);

// Professores.
$router->get('/dashboard/professores', [ProfessorController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/professores/novo', [ProfessorController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/professores', [ProfessorController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/professores/{id}/editar', [ProfessorController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/professores/{id}', [ProfessorController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/professores/{id}/excluir', [ProfessorController::class, 'destroy'], [AuthMiddleware::class]);

// Planos de matricula.
$router->get('/dashboard/planos-matricula', [PlanoMatriculaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/planos-matricula/novo', [PlanoMatriculaController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/planos-matricula', [PlanoMatriculaController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/planos-matricula/{id}/editar', [PlanoMatriculaController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/planos-matricula/{id}', [PlanoMatriculaController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/planos-matricula/{id}/excluir', [PlanoMatriculaController::class, 'destroy'], [AuthMiddleware::class]);

// Financeiro (caixa + lancamentos).
$router->get('/dashboard/financeiro', [FinanceiroController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/caixa/abrir', [FinanceiroController::class, 'abrirCaixa'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/caixa/fechar', [FinanceiroController::class, 'fecharCaixa'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/lancamentos', [FinanceiroController::class, 'store'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/lancamentos/{id}/excluir', [FinanceiroController::class, 'destroy'], [AuthMiddleware::class]);

// Configuracoes (dados da academia + equipe) - so admin.
$router->get('/dashboard/configuracoes', [ConfiguracaoController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/plano', [ConfiguracaoController::class, 'trocarPlano'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/assinatura/cancelar', [ConfiguracaoController::class, 'cancelarAssinatura'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/assinatura/reativar', [ConfiguracaoController::class, 'reativarAssinatura'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/pix', [ConfiguracaoController::class, 'salvarPix'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/perfil', [ConfiguracaoController::class, 'atualizarPerfil'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/equipe', [ConfiguracaoController::class, 'criarUsuario'], [AuthMiddleware::class]);
$router->get('/dashboard/configuracoes/equipe/{id}/editar', [ConfiguracaoController::class, 'editarUsuario'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/equipe/{id}', [ConfiguracaoController::class, 'atualizarUsuario'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/equipe/{id}/excluir', [ConfiguracaoController::class, 'excluirUsuario'], [AuthMiddleware::class]);
