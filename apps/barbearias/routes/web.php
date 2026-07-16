<?php

declare(strict_types=1);

use Barbearias\Controllers\AgendamentoController;
use Barbearias\Controllers\AgendamentoPublicoController;
use Barbearias\Controllers\AssinaturaClienteController;
use Barbearias\Controllers\AssinaturaController;
use Barbearias\Controllers\AuthController;
use Barbearias\Controllers\BloqueioController;
use Barbearias\Controllers\CadastroController;
use Barbearias\Controllers\ClienteAreaController;
use Barbearias\Controllers\ClienteController;
use Barbearias\Controllers\ComissaoController;
use Barbearias\Controllers\ConfiguracaoController;
use Barbearias\Controllers\CrmController;
use Barbearias\Controllers\DashboardController;
use Barbearias\Controllers\FaturaController;
use Barbearias\Controllers\FidelidadeController;
use Barbearias\Controllers\FilaController;
use Barbearias\Controllers\FilaPublicaController;
use Barbearias\Controllers\FinanceiroController;
use Barbearias\Controllers\LandingController;
use Barbearias\Controllers\ListaEsperaController;
use Barbearias\Controllers\PortfolioController;
use Barbearias\Controllers\ProdutoController;
use Barbearias\Controllers\ProfissionalController;
use Barbearias\Controllers\RecepcaoController;
use Barbearias\Controllers\RelatorioController;
use Barbearias\Controllers\ServicoController;
use Barbearias\Controllers\UnidadeController;
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

// Agendamento publico (cliente final, sem login) - link compartilhavel
// da barbearia (Instagram/WhatsApp).
$router->get('/agendar/{slug}', [AgendamentoPublicoController::class, 'form']);
$router->get('/agendar/{slug}/horarios', [AgendamentoPublicoController::class, 'horarios']);
$router->post('/agendar/{slug}', [AgendamentoPublicoController::class, 'enviar']);
$router->post('/agendar/{slug}/lista-espera', [AgendamentoPublicoController::class, 'listaEspera']);
$router->get('/agendar/{slug}/confirmado', [AgendamentoPublicoController::class, 'confirmado']);

// Area do cliente (/minha-conta/{slug}) - login proprio do cliente
// final, separado da equipe.
$router->get('/minha-conta/{slug}', [ClienteAreaController::class, 'painel']);
$router->get('/minha-conta/{slug}/entrar', [ClienteAreaController::class, 'showEntrar']);
$router->post('/minha-conta/{slug}/entrar', [ClienteAreaController::class, 'entrar']);
$router->get('/minha-conta/{slug}/cadastro', [ClienteAreaController::class, 'showCadastro']);
$router->post('/minha-conta/{slug}/cadastro', [ClienteAreaController::class, 'cadastro']);
$router->post('/minha-conta/{slug}/sair', [ClienteAreaController::class, 'sair']);
$router->post('/minha-conta/{slug}/avaliacoes/{agendamentoId}', [ClienteAreaController::class, 'avaliar']);
$router->post('/minha-conta/{slug}/agendamentos/{agendamentoId}/cancelar', [ClienteAreaController::class, 'cancelar']);
$router->get('/minha-conta/{slug}/agendamentos/{agendamentoId}/reagendar', [ClienteAreaController::class, 'reagendarForm']);
$router->get('/minha-conta/{slug}/agendamentos/{agendamentoId}/reagendar/horarios', [ClienteAreaController::class, 'horariosParaReagendar']);
$router->post('/minha-conta/{slug}/agendamentos/{agendamentoId}/reagendar', [ClienteAreaController::class, 'reagendar']);

// Fila de espera publica (sem login) - alternativa ao agendamento, so
// pra barbearias com modo_atendimento = 'fila'.
$router->get('/fila/{slug}', [FilaPublicaController::class, 'mostrar']);
$router->post('/fila/{slug}', [FilaPublicaController::class, 'entrar']);
$router->get('/fila/{slug}/confirmado', [FilaPublicaController::class, 'confirmado']);

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

// Unidades (filiais).
$router->get('/dashboard/unidades', [UnidadeController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/unidades/nova', [UnidadeController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/unidades', [UnidadeController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/unidades/{id}/editar', [UnidadeController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/unidades/{id}', [UnidadeController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/unidades/{id}/excluir', [UnidadeController::class, 'destroy'], [AuthMiddleware::class]);

// Profissionais.
$router->get('/dashboard/profissionais', [ProfissionalController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/profissionais/novo', [ProfissionalController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/profissionais', [ProfissionalController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/profissionais/{id}/editar', [ProfissionalController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/profissionais/{id}', [ProfissionalController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/profissionais/{id}/excluir', [ProfissionalController::class, 'destroy'], [AuthMiddleware::class]);
$router->post('/dashboard/profissionais/{id}/portfolio', [PortfolioController::class, 'upload'], [AuthMiddleware::class]);
$router->post('/dashboard/portfolio/{id}/excluir', [PortfolioController::class, 'destroy'], [AuthMiddleware::class]);

// Servicos.
$router->get('/dashboard/servicos', [ServicoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/servicos/novo', [ServicoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/servicos', [ServicoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/servicos/{id}/editar', [ServicoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/servicos/{id}', [ServicoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/servicos/{id}/excluir', [ServicoController::class, 'destroy'], [AuthMiddleware::class]);

// Clientes.
$router->get('/dashboard/clientes', [ClienteController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/clientes/novo', [ClienteController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/clientes', [ClienteController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/clientes/{id}/editar', [ClienteController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/clientes/{id}', [ClienteController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/clientes/{id}/excluir', [ClienteController::class, 'destroy'], [AuthMiddleware::class]);

// Agendamentos.
$router->get('/dashboard/agendamentos', [AgendamentoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/agendamentos/novo', [AgendamentoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/agendamentos', [AgendamentoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/agendamentos/{id}/editar', [AgendamentoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/agendamentos/{id}', [AgendamentoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/agendamentos/{id}/status', [AgendamentoController::class, 'status'], [AuthMiddleware::class]);
$router->get('/dashboard/agendamentos/{id}/pagamento', [AgendamentoController::class, 'pagamentoForm'], [AuthMiddleware::class]);
$router->post('/dashboard/agendamentos/{id}/pagamento', [AgendamentoController::class, 'pagamento'], [AuthMiddleware::class]);
$router->post('/dashboard/agendamentos/{id}/usar-assinatura', [AgendamentoController::class, 'usarAssinatura'], [AuthMiddleware::class]);
$router->post('/dashboard/agendamentos/{id}/excluir', [AgendamentoController::class, 'destroy'], [AuthMiddleware::class]);

// Fila de atendimento (equipe) - alternativa ao Agendamento, so pra
// barbearias com modo_atendimento = 'fila'.
$router->get('/dashboard/fila', [FilaController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/fila', [FilaController::class, 'adicionar'], [AuthMiddleware::class]);
$router->post('/dashboard/fila/{id}/chamar', [FilaController::class, 'chamar'], [AuthMiddleware::class]);
$router->post('/dashboard/fila/{id}/concluir', [FilaController::class, 'concluir'], [AuthMiddleware::class]);
$router->post('/dashboard/fila/{id}/cancelar', [FilaController::class, 'cancelar'], [AuthMiddleware::class]);

// Bloqueios de agenda (ferias, folgas, compromissos pontuais).
$router->get('/dashboard/bloqueios', [BloqueioController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/bloqueios/novo', [BloqueioController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/bloqueios', [BloqueioController::class, 'store'], [AuthMiddleware::class]);
$router->post('/dashboard/bloqueios/{id}/excluir', [BloqueioController::class, 'destroy'], [AuthMiddleware::class]);

// CRM (aniversariantes do mes + clientes inativos).
$router->get('/dashboard/crm', [CrmController::class, 'index'], [AuthMiddleware::class]);

// Lista de espera (quem tentou agendar sem achar horario livre).
$router->get('/dashboard/lista-espera', [ListaEsperaController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/lista-espera/{id}/atender', [ListaEsperaController::class, 'atender'], [AuthMiddleware::class]);
$router->post('/dashboard/lista-espera/{id}/cancelar', [ListaEsperaController::class, 'cancelar'], [AuthMiddleware::class]);

// Faturas (historico de cobranca - sempre acessivel, mesmo bloqueado).
$router->get('/dashboard/faturas', [FaturaController::class, 'index'], [AuthMiddleware::class]);

// Financeiro (caixa diario + lancamentos de receita/despesa).
$router->get('/dashboard/financeiro', [FinanceiroController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/caixa/abrir', [FinanceiroController::class, 'abrirCaixa'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/caixa/fechar', [FinanceiroController::class, 'fecharCaixa'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/lancamentos', [FinanceiroController::class, 'store'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro/lancamentos/{id}/excluir', [FinanceiroController::class, 'destroy'], [AuthMiddleware::class]);

// Comissoes (fechamento por profissional, com base nos atendimentos concluidos).
$router->get('/dashboard/comissoes', [ComissaoController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/comissoes/{id}/pagar', [ComissaoController::class, 'pagar'], [AuthMiddleware::class]);

// Painel de recepcao (tela cheia, pensada pra ficar numa TV do salao).
$router->get('/dashboard/recepcao', [RecepcaoController::class, 'index'], [AuthMiddleware::class]);

// Assinaturas de cliente (pacotes pre-pagos de N atendimentos por mes).
$router->get('/dashboard/assinaturas-clientes', [AssinaturaClienteController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/assinaturas-clientes/planos', [AssinaturaClienteController::class, 'planoStore'], [AuthMiddleware::class]);
$router->post('/dashboard/assinaturas-clientes/planos/{id}/excluir', [AssinaturaClienteController::class, 'planoDestroy'], [AuthMiddleware::class]);
$router->post('/dashboard/assinaturas-clientes/assinar', [AssinaturaClienteController::class, 'assinar'], [AuthMiddleware::class]);
$router->post('/dashboard/assinaturas-clientes/{id}/cancelar', [AssinaturaClienteController::class, 'cancelar'], [AuthMiddleware::class]);

// Fidelidade (pontos por atendimento pago, resgatados por recompensas).
$router->get('/dashboard/fidelidade', [FidelidadeController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/fidelidade/configurar', [FidelidadeController::class, 'configurar'], [AuthMiddleware::class]);
$router->post('/dashboard/fidelidade/recompensas', [FidelidadeController::class, 'recompensaStore'], [AuthMiddleware::class]);
$router->post('/dashboard/fidelidade/recompensas/{id}/excluir', [FidelidadeController::class, 'recompensaDestroy'], [AuthMiddleware::class]);
$router->post('/dashboard/fidelidade/resgatar', [FidelidadeController::class, 'resgatar'], [AuthMiddleware::class]);

// Relatorios consolidados (faturamento, agendamentos, ticket medio, ocupacao).
$router->get('/dashboard/relatorios', [RelatorioController::class, 'index'], [AuthMiddleware::class]);

// Produtos (catalogo + estoque + venda avulsa).
$router->get('/dashboard/produtos', [ProdutoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/produtos/novo', [ProdutoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos', [ProdutoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/produtos/{id}/editar', [ProdutoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos/{id}', [ProdutoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos/{id}/excluir', [ProdutoController::class, 'destroy'], [AuthMiddleware::class]);
$router->post('/dashboard/produtos/{id}/vender', [ProdutoController::class, 'vender'], [AuthMiddleware::class]);

// Configuracoes (dados da barbearia + equipe) - so admin.
$router->get('/dashboard/configuracoes', [ConfiguracaoController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/plano', [ConfiguracaoController::class, 'trocarPlano'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/modo-atendimento', [ConfiguracaoController::class, 'atualizarModoAtendimento'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/pix', [ConfiguracaoController::class, 'salvarPix'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/perfil', [ConfiguracaoController::class, 'atualizarPerfil'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/equipe', [ConfiguracaoController::class, 'criarUsuario'], [AuthMiddleware::class]);
$router->get('/dashboard/configuracoes/equipe/{id}/editar', [ConfiguracaoController::class, 'editarUsuario'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/equipe/{id}', [ConfiguracaoController::class, 'atualizarUsuario'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/equipe/{id}/excluir', [ConfiguracaoController::class, 'excluirUsuario'], [AuthMiddleware::class]);
