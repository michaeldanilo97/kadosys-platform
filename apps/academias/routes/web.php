<?php

declare(strict_types=1);

use Academias\Controllers\AlunoAreaController;
use Academias\Controllers\AlunoController;
use Academias\Controllers\AssinaturaController;
use Academias\Controllers\AuthController;
use Academias\Controllers\AvaliacaoController;
use Academias\Controllers\AvaliacaoFisicaController;
use Academias\Controllers\CadastroController;
use Academias\Controllers\CheckinController;
use Academias\Controllers\ConfiguracaoController;
use Academias\Controllers\DashboardController;
use Academias\Controllers\FaturaController;
use Academias\Controllers\FichaTreinoController;
use Academias\Controllers\FinanceiroController;
use Academias\Controllers\LandingController;
use Academias\Controllers\PlanoMatriculaController;
use Academias\Controllers\ProfessorController;
use Academias\Controllers\TreinoController;
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

// Check-in publico via QR fixo (exige aluno logado - ver CheckinController::processar,
// que redireciona pro login da area do aluno com "next" de volta pra essa URL).
$router->get('/checkin/{slug}/{token}', [CheckinController::class, 'processar']);

// Area do aluno (login proprio, separado da equipe - ver Academias\Core\AlunoAuth).
$router->get('/minha-conta/{slug}', [AlunoAreaController::class, 'painel']);
$router->get('/minha-conta/{slug}/entrar', [AlunoAreaController::class, 'showEntrar']);
$router->post('/minha-conta/{slug}/entrar', [AlunoAreaController::class, 'entrar']);
$router->get('/minha-conta/{slug}/cadastro', [AlunoAreaController::class, 'showCadastro']);
$router->post('/minha-conta/{slug}/cadastro', [AlunoAreaController::class, 'cadastro']);
$router->post('/minha-conta/{slug}/sair', [AlunoAreaController::class, 'sair']);

// Treino do dia (area do aluno) - listar fichas ativas, ver exercicios
// e marcar carga usada, que alimenta o grafico de evolucao.
$router->get('/minha-conta/{slug}/treino', [TreinoController::class, 'index']);
$router->get('/minha-conta/{slug}/treino/{fichaId}', [TreinoController::class, 'show']);
$router->post('/minha-conta/{slug}/treino/{fichaId}/exercicios/{exercicioId}', [TreinoController::class, 'registrar']);

// Minha avaliacao fisica (area do aluno - so leitura, quem registra e a equipe).
$router->get('/minha-conta/{slug}/avaliacao', [AvaliacaoController::class, 'index']);

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

// Check-in (equipe, dashboard) - quem esta dentro agora, QR, historico, ranking.
$router->get('/dashboard/checkin', [CheckinController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/checkin/qr', [CheckinController::class, 'qr'], [AuthMiddleware::class]);
$router->post('/dashboard/checkin/qr/regenerar', [CheckinController::class, 'regenerarToken'], [AuthMiddleware::class]);
$router->get('/dashboard/checkin/historico', [CheckinController::class, 'historico'], [AuthMiddleware::class]);
$router->get('/dashboard/ranking', [CheckinController::class, 'ranking'], [AuthMiddleware::class]);

// Alunos.
$router->get('/dashboard/alunos', [AlunoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/alunos/novo', [AlunoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/alunos', [AlunoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/alunos/{id}/editar', [AlunoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/alunos/{id}', [AlunoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/alunos/{id}/excluir', [AlunoController::class, 'destroy'], [AuthMiddleware::class]);

// Fichas de treino (equipe/professor) - CRUD da ficha + exercicios dela.
$router->get('/dashboard/fichas-treino', [FichaTreinoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/fichas-treino/novo', [FichaTreinoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/fichas-treino', [FichaTreinoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/fichas-treino/{id}/editar', [FichaTreinoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/fichas-treino/{id}', [FichaTreinoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/fichas-treino/{id}/excluir', [FichaTreinoController::class, 'destroy'], [AuthMiddleware::class]);
$router->post('/dashboard/fichas-treino/{id}/exercicios', [FichaTreinoController::class, 'storeExercicio'], [AuthMiddleware::class]);
$router->post('/dashboard/fichas-treino/{id}/exercicios/{exercicioId}', [FichaTreinoController::class, 'updateExercicio'], [AuthMiddleware::class]);
$router->post('/dashboard/fichas-treino/{id}/exercicios/{exercicioId}/excluir', [FichaTreinoController::class, 'destroyExercicio'], [AuthMiddleware::class]);

// Avaliacao fisica (equipe/professor) - bioimpedancia simplificada.
$router->get('/dashboard/avaliacoes-fisicas', [AvaliacaoFisicaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/avaliacoes-fisicas/novo', [AvaliacaoFisicaController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/avaliacoes-fisicas', [AvaliacaoFisicaController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/avaliacoes-fisicas/{id}/editar', [AvaliacaoFisicaController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/avaliacoes-fisicas/{id}', [AvaliacaoFisicaController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/avaliacoes-fisicas/{id}/excluir', [AvaliacaoFisicaController::class, 'destroy'], [AuthMiddleware::class]);

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
