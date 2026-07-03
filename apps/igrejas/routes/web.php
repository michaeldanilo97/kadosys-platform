<?php

declare(strict_types=1);

use Igrejas\Controllers\AssinaturaController;
use Igrejas\Controllers\AuthController;
use Igrejas\Controllers\CadastroController;
use Igrejas\Controllers\ConfiguracaoController;
use Igrejas\Controllers\CultoController;
use Igrejas\Controllers\DashboardController;
use Igrejas\Controllers\LandingController;
use Igrejas\Controllers\MembroController;
use Igrejas\Controllers\MinisterioController;
use Igrejas\Controllers\PlataformaController;
use Igrejas\Controllers\PreletorController;
use Igrejas\Controllers\ProjecaoController;
use Igrejas\Controllers\ProjecaoEstadoController;
use Igrejas\Controllers\TelaoController;
use Igrejas\Core\Middleware\AuthMiddleware;
use Igrejas\Core\Middleware\GuestMiddleware;
use Igrejas\Core\Middleware\PlanoMiddleware;
use Igrejas\Core\Middleware\PlataformaAuthMiddleware;

/**
 * Rotas web - KADOSYS Igrejas.
 *
 * @var \Igrejas\Core\Router $router
 */

// Landing page publica.
$router->get('/', [LandingController::class, 'index']);

// Cadastro publico autoatendido (igreja + administrador + plano),
// redireciona pro checkout do Mercado Pago. O provisionamento em si
// (criar banco, subdominio) acontece via webhook, nao aqui.
$router->get('/cadastro', [CadastroController::class, 'form'], [GuestMiddleware::class]);
$router->post('/cadastro', [CadastroController::class, 'enviar'], [GuestMiddleware::class]);
$router->get('/cadastro/pix/{id}', [CadastroController::class, 'pix']);
$router->get('/cadastro/pix/{id}/status', [CadastroController::class, 'pixStatus']);
$router->get('/cadastro/retorno', [CadastroController::class, 'retorno']);

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
// PlanoMiddleware: Ministerios exige plano Premium ou superior (ver
// Igrejas\Models\Plano).
$router->get('/dashboard/ministerios', [MinisterioController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/ministerios/novo', [MinisterioController::class, 'create'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/ministerios', [MinisterioController::class, 'store'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/ministerios/{id}/editar', [MinisterioController::class, 'edit'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/ministerios/{id}', [MinisterioController::class, 'update'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/ministerios/{id}/excluir', [MinisterioController::class, 'destroy'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/ministerios/{id}/voluntarios', [MinisterioController::class, 'addVoluntario'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/ministerios/{id}/voluntarios/{membroId}/remover', [MinisterioController::class, 'removeVoluntario'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Modulo Cultos. Mesmo motivo: precisam vir antes do catch-all.
$router->get('/dashboard/cultos', [CultoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/cultos/novo', [CultoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos', [CultoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/cultos/{id}/editar', [CultoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}', [CultoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}/excluir', [CultoController::class, 'destroy'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}/presencas', [CultoController::class, 'addPresenca'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}/presencas/{membroId}/remover', [CultoController::class, 'removePresenca'], [AuthMiddleware::class]);

// Modulo Projecao/Telao: painel do operador (protegido). Mesmo motivo:
// precisa vir antes do catch-all.
$router->get('/dashboard/projecao', [ProjecaoController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/projecao/iniciar', [ProjecaoController::class, 'iniciar'], [AuthMiddleware::class]);
$router->post('/dashboard/projecao/encerrar', [ProjecaoController::class, 'encerrar'], [AuthMiddleware::class]);

// Configuracoes (logo da igreja, usada no fadeout da projecao, e o plano
// contratado). Mesmo motivo: precisa vir antes do catch-all. Sem
// PlanoMiddleware de proposito - precisa continuar acessivel mesmo se a
// igreja estiver num plano que bloquearia outros modulos, senao nao
// haveria como ver/trocar o plano pela propria interface.
$router->get('/dashboard/configuracoes', [ConfiguracaoController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/logo', [ConfiguracaoController::class, 'atualizarLogo'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/logo/remover', [ConfiguracaoController::class, 'removerLogo'], [AuthMiddleware::class]);

// Assinatura recorrente do plano via Mercado Pago (Checkout Pro). Sem
// PlanoMiddleware pelo mesmo motivo das rotas de Configuracoes acima.
$router->post('/dashboard/configuracoes/assinatura/{plano}', [AssinaturaController::class, 'iniciar'], [AuthMiddleware::class]);
$router->get('/dashboard/assinatura/retorno', [AssinaturaController::class, 'retorno'], [AuthMiddleware::class]);

// Tela exibida quando um modulo fora do plano contratado e acessado (ver
// PlanoMiddleware). Tambem sem PlanoMiddleware, para nao criar um loop de
// redirecionamento.
$router->get('/dashboard/plano-bloqueado', [DashboardController::class, 'planoBloqueado'], [AuthMiddleware::class]);

// Tela exibida quando a fatura Pix de renovacao mensal vence sem
// pagamento (ver AuthMiddleware) - mostra o QR code pra pagar e libera o
// acesso de volta assim que o webhook confirmar.
$router->get('/dashboard/fatura-vencida', [ConfiguracaoController::class, 'faturaVencida'], [AuthMiddleware::class]);
$router->get('/dashboard/fatura-vencida/status', [ConfiguracaoController::class, 'faturaVencidaStatus'], [AuthMiddleware::class]);

// Tela exibida quando o teste gratis de 7 dias vence sem a igreja
// escolher um plano pago (ver AuthMiddleware).
$router->get('/dashboard/trial-expirado', [ConfiguracaoController::class, 'trialExpirado'], [AuthMiddleware::class]);

// Estrutura "em construcao" dos demais modulos do menu (catch-all).
// PlanoMiddleware aqui cobre todos os modulos sem controller proprio
// (grupos, agenda, financeiro, patrimonio, comunicacao, relatorios,
// usuarios, permissoes) de uma vez so, com base no slug da propria URI.
$router->get('/dashboard/{slug}', [DashboardController::class, 'page'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Tela publica do telao (projetor). Acesso direto por link com token,
// sem exigir login administrativo.
$router->get('/telao/{token}', [TelaoController::class, 'show']);

// Tela publica do preletor (tablet do pastor). Acesso por PIN de 6
// digitos, sem exigir login administrativo.
$router->get('/preletor', [PreletorController::class, 'entrar']);
$router->post('/preletor', [PreletorController::class, 'autenticar']);
$router->get('/preletor/painel', [PreletorController::class, 'painel']);
$router->post('/preletor/sair', [PreletorController::class, 'sair']);

// Estado da projecao (JSON): leitura/escrita autorizadas pelo token da
// sessao, usadas via polling pelas 3 telas (operador, telao, preletor).
$router->get('/projecao/{token}/estado', [ProjecaoEstadoController::class, 'estado']);
$router->post('/projecao/{token}/biblia', [ProjecaoEstadoController::class, 'definirBiblia']);
$router->post('/projecao/{token}/biblia/navegar', [ProjecaoEstadoController::class, 'navegarBiblia']);
$router->get('/projecao/{token}/biblia/capitulo', [ProjecaoEstadoController::class, 'capituloInfo']);
$router->post('/projecao/{token}/biblia/marcacao', [ProjecaoEstadoController::class, 'definirMarcacao']);
$router->post('/projecao/{token}/video', [ProjecaoEstadoController::class, 'definirVideo']);
$router->post('/projecao/{token}/video/estado', [ProjecaoEstadoController::class, 'definirEstadoVideo']);
$router->post('/projecao/{token}/video/tempo', [ProjecaoEstadoController::class, 'atualizarTempoVideo']);
$router->post('/projecao/{token}/logo', [ProjecaoEstadoController::class, 'mostrarLogo']);
$router->post('/projecao/{token}/limpar', [ProjecaoEstadoController::class, 'limpar']);

// Webhook do Mercado Pago (notificacoes de assinatura/pagamento). Publica
// de proposito - a seguranca aqui vem da validacao da assinatura
// "x-signature" dentro do proprio controller, nao de sessao/CSRF (quem
// chama e o servidor do Mercado Pago, nao um navegador logado).
$router->post('/webhooks/mercadopago', [AssinaturaController::class, 'webhook']);

// Painel administrativo da plataforma (dono do sistema) - lista e
// exclui igrejas provisionadas automaticamente. Autenticacao propria
// (chave mestra, ver config/plataforma.php), totalmente separada do
// login normal de cada igreja (AuthMiddleware).
$router->get('/plataforma/entrar', [PlataformaController::class, 'entrar']);
$router->post('/plataforma/entrar', [PlataformaController::class, 'autenticar']);
$router->post('/plataforma/sair', [PlataformaController::class, 'sair'], [PlataformaAuthMiddleware::class]);
$router->get('/plataforma/igrejas', [PlataformaController::class, 'igrejas'], [PlataformaAuthMiddleware::class]);
$router->post('/plataforma/igrejas/{id}/excluir', [PlataformaController::class, 'excluirIgreja'], [PlataformaAuthMiddleware::class]);
