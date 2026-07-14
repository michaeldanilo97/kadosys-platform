<?php

declare(strict_types=1);

use Igrejas\Controllers\AgendaController;
use Igrejas\Controllers\AssinaturaController;
use Igrejas\Controllers\AuthController;
use Igrejas\Controllers\AvisoPublicoController;
use Igrejas\Controllers\CadastroController;
use Igrejas\Controllers\ComunicacaoController;
use Igrejas\Controllers\ConfiguracaoController;
use Igrejas\Controllers\CultoController;
use Igrejas\Controllers\DashboardController;
use Igrejas\Controllers\DoacaoController;
use Igrejas\Controllers\EquipeController;
use Igrejas\Controllers\FaturaController;
use Igrejas\Controllers\FinanceiroController;
use Igrejas\Controllers\GrupoController;
use Igrejas\Controllers\KidsBibliotecaController;
use Igrejas\Controllers\KidsCheckinController;
use Igrejas\Controllers\KidsController;
use Igrejas\Controllers\KidsConteudoController;
use Igrejas\Controllers\KidsCriancaController;
use Igrejas\Controllers\KidsTurmaController;
use Igrejas\Controllers\LandingController;
use Igrejas\Controllers\RecursoController;
use Igrejas\Controllers\LouvorController;
use Igrejas\Controllers\MembroController;
use Igrejas\Controllers\MinisterioController;
use Igrejas\Controllers\PatrimonioController;
use Igrejas\Controllers\PerfilController;
use Igrejas\Controllers\PermissaoController;
use Igrejas\Controllers\PlataformaController;
use Igrejas\Controllers\PlaybackController;
use Igrejas\Controllers\PreletorController;
use Igrejas\Controllers\ProjecaoController;
use Igrejas\Controllers\ProjecaoEstadoController;
use Igrejas\Controllers\RelatorioController;
use Igrejas\Controllers\RepertorioController;
use Igrejas\Controllers\TelaoController;
use Igrejas\Controllers\UsuarioController;
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

// Paginas dedicadas de cada modulo no site institucional (recursos e
// diferenciais detalhados, com screenshot real do sistema).
$router->get('/recursos/{slug}', [RecursoController::class, 'show']);

// Cadastro publico autoatendido (igreja + administrador + plano),
// redireciona pro checkout do Mercado Pago. O provisionamento em si
// (criar banco, subdominio) acontece via webhook, nao aqui (exceto no
// teste gratis, que e sincrono - ver CadastroController::criarContaTrial()).
// Dentro do subdominio de uma igreja ja existente, a mesma URL delega
// pro auto-cadastro publico de membros (ver MembroPublicoController).
$router->get('/cadastro', [CadastroController::class, 'form'], [GuestMiddleware::class]);
$router->post('/cadastro', [CadastroController::class, 'enviar'], [GuestMiddleware::class]);
$router->get('/cadastro/pix/{id}', [CadastroController::class, 'pix']);
$router->get('/cadastro/pix/{id}/status', [CadastroController::class, 'pixStatus']);
$router->get('/cadastro/retorno', [CadastroController::class, 'retorno']);
$router->get('/cadastro/pronto/{id}', [CadastroController::class, 'pronto']);
$router->get('/cadastro/pronto/{id}/status', [CadastroController::class, 'prontoStatus']);

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
$router->post('/dashboard/membros/{id}', [MembroController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/membros/{id}/excluir', [MembroController::class, 'destroy'], [AuthMiddleware::class]);
$router->post('/dashboard/membros/{id}/documentos', [MembroController::class, 'documentoStore'], [AuthMiddleware::class]);
$router->post('/dashboard/membros/{id}/documentos/{documentoId}/excluir', [MembroController::class, 'documentoDestroy'], [AuthMiddleware::class]);
// Precisa vir por ultimo entre as rotas de membros: e a mais generica
// ({id} sozinho) e casaria com "novo" se viesse antes dela.
$router->get('/dashboard/membros/{id}', [MembroController::class, 'show'], [AuthMiddleware::class]);

// Equipe (galeria estilo rede social) e Meu Perfil (autoatendimento) -
// mesmo motivo acima, precisam vir antes do catch-all de modulo.
$router->get('/dashboard/equipe', [EquipeController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/equipe/{id}', [EquipeController::class, 'show'], [AuthMiddleware::class]);
$router->get('/dashboard/perfil', [PerfilController::class, 'editar'], [AuthMiddleware::class]);
$router->post('/dashboard/perfil', [PerfilController::class, 'atualizar'], [AuthMiddleware::class]);

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

// Modulo Grupos. Mesmo motivo: precisam vir antes do catch-all.
// PlanoMiddleware: Grupos exige plano Premium ou superior (ver
// Igrejas\Models\Plano), mesma estrutura do modulo Ministerios.
$router->get('/dashboard/grupos', [GrupoController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/grupos/novo', [GrupoController::class, 'create'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/grupos', [GrupoController::class, 'store'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/grupos/{id}/editar', [GrupoController::class, 'edit'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/grupos/{id}', [GrupoController::class, 'update'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/grupos/{id}/excluir', [GrupoController::class, 'destroy'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/grupos/{id}/participantes', [GrupoController::class, 'addParticipante'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/grupos/{id}/participantes/{membroId}/remover', [GrupoController::class, 'removeParticipante'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Modulo KADOSYS Kids (Fase 1 - operacional: Turmas, Crianças e
// Check-in). Mesmo motivo: precisam vir antes do catch-all.
// PlanoMiddleware: Kids exige plano Plus ou superior (ver
// Igrejas\Models\Plano), mesmo nivel de Ministerios/Grupos/Comunicacao.
$router->get('/dashboard/kids', [KidsController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);

$router->get('/dashboard/kids/turmas', [KidsTurmaController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/kids/turmas/novo', [KidsTurmaController::class, 'create'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/turmas', [KidsTurmaController::class, 'store'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/kids/turmas/{id}/editar', [KidsTurmaController::class, 'edit'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/turmas/{id}', [KidsTurmaController::class, 'update'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/turmas/{id}/excluir', [KidsTurmaController::class, 'destroy'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Check-in precisa vir antes de "/dashboard/kids/criancas/{id}" - mesmo
// motivo de sempre, e um caminho fixo (nao um {id}).
$router->get('/dashboard/kids/checkin', [KidsCheckinController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/checkin', [KidsCheckinController::class, 'registrar'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/checkin/{id}/encerrar', [KidsCheckinController::class, 'encerrar'], [AuthMiddleware::class, PlanoMiddleware::class]);

$router->get('/dashboard/kids/criancas', [KidsCriancaController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/kids/criancas/novo', [KidsCriancaController::class, 'create'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/criancas', [KidsCriancaController::class, 'store'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/kids/criancas/{id}/editar', [KidsCriancaController::class, 'edit'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/criancas/{id}', [KidsCriancaController::class, 'update'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/criancas/{id}/excluir', [KidsCriancaController::class, 'destroy'], [AuthMiddleware::class, PlanoMiddleware::class]);
// Precisa vir por ultimo entre as rotas de criancas: e a mais generica
// ({id} sozinho) e casaria com "novo" se viesse antes dela.
$router->get('/dashboard/kids/criancas/{id}', [KidsCriancaController::class, 'show'], [AuthMiddleware::class, PlanoMiddleware::class]);

// CMS de conteudo (Kids > Conteúdos - historias, videos, quiz etc.
// criados pela propria igreja; o conteudo oficial "kadosys" aparece
// na mesma lista, so leitura). Mesmo motivo de sempre: precisa vir
// antes do catch-all.
$router->get('/dashboard/kids/conteudos', [KidsConteudoController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/kids/conteudos/novo', [KidsConteudoController::class, 'create'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/conteudos', [KidsConteudoController::class, 'store'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/kids/conteudos/{id}/editar', [KidsConteudoController::class, 'edit'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/conteudos/{id}', [KidsConteudoController::class, 'update'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/conteudos/{id}/excluir', [KidsConteudoController::class, 'destroy'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Biblioteca Kids no "modo criança" (tela colorida/cartoon - ver
// KidsBibliotecaController). Mesmo motivo: precisa vir antes do
// catch-all.
$router->get('/dashboard/kids/biblioteca', [KidsBibliotecaController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/biblioteca/crianca', [KidsBibliotecaController::class, 'selecionarCrianca'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/kids/biblioteca/tipo/{tipo}', [KidsBibliotecaController::class, 'porTipo'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/kids/biblioteca/conteudo/{id}', [KidsBibliotecaController::class, 'show'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/kids/biblioteca/conteudo/{id}/concluir', [KidsBibliotecaController::class, 'concluir'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Modulo Cultos. Mesmo motivo: precisam vir antes do catch-all.
$router->get('/dashboard/cultos', [CultoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/cultos/novo', [CultoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos', [CultoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/cultos/{id}/editar', [CultoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}', [CultoController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}/excluir', [CultoController::class, 'destroy'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}/presencas', [CultoController::class, 'addPresenca'], [AuthMiddleware::class]);
$router->post('/dashboard/cultos/{id}/presencas/{membroId}/remover', [CultoController::class, 'removePresenca'], [AuthMiddleware::class]);

// Modulo Playbacks. Mesmo motivo: precisa vir antes do catch-all. Sem
// PlanoMiddleware - Playbacks (biblioteca de audios) esta liberado em
// todos os planos.
$router->get('/dashboard/playbacks', [PlaybackController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/playbacks/novo', [PlaybackController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/playbacks', [PlaybackController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/playbacks/{id}/editar', [PlaybackController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/playbacks/{id}', [PlaybackController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/playbacks/{id}/excluir', [PlaybackController::class, 'destroy'], [AuthMiddleware::class]);

$router->get('/dashboard/louvores', [LouvorController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/louvores/novo', [LouvorController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores', [LouvorController::class, 'store'], [AuthMiddleware::class]);

// Programacao de culto (repertorio) - precisa vir ANTES de qualquer
// "/dashboard/louvores/{id}" (GET ou POST, inclusive so 3 segmentos
// como o de update abaixo), senao "repertorios" seria interpretado
// como um {id} de louvor por essas rotas mais genericas.
$router->get('/dashboard/louvores/repertorios', [RepertorioController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/louvores/repertorios/novo', [RepertorioController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/repertorios', [RepertorioController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/louvores/repertorios/{id}/editar', [RepertorioController::class, 'editor'], [AuthMiddleware::class]);
$router->get('/dashboard/louvores/repertorios/{id}/culto', [RepertorioController::class, 'culto'], [AuthMiddleware::class]);
$router->get('/dashboard/louvores/repertorios/{id}/estado', [RepertorioController::class, 'estado'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/repertorios/{id}/itens', [RepertorioController::class, 'adicionarItem'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/repertorios/{id}/itens/{itemId}/remover', [RepertorioController::class, 'removerItem'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/repertorios/{id}/itens/{itemId}/tom', [RepertorioController::class, 'alterarTom'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/repertorios/{id}/reordenar', [RepertorioController::class, 'reordenar'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/repertorios/{id}/avancar', [RepertorioController::class, 'avancar'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/repertorios/{id}/voltar', [RepertorioController::class, 'voltar'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/repertorios/{id}/encerrar', [RepertorioController::class, 'encerrar'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/repertorios/{id}/mensagens', [RepertorioController::class, 'mensagem'], [AuthMiddleware::class]);

$router->get('/dashboard/louvores/{id}/editar', [LouvorController::class, 'edit'], [AuthMiddleware::class]);
$router->get('/dashboard/louvores/{id}/tela-cheia', [LouvorController::class, 'telaCheia'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/{id}/anotacao', [LouvorController::class, 'salvarAnotacao'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/{id}', [LouvorController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/louvores/{id}/excluir', [LouvorController::class, 'destroy'], [AuthMiddleware::class]);
$router->get('/dashboard/louvores/{id}', [LouvorController::class, 'show'], [AuthMiddleware::class]);

// Modulo Agenda. Mesmo motivo: precisa vir antes do catch-all. Sem
// PlanoMiddleware - Agenda esta liberada em todos os planos, mesmo
// nivel de acesso de Membros/Cultos. O calendario (cultos + eventos +
// aniversariantes do mes) e a tela principal; a lista antiga continua
// disponivel em /lista pra gerenciar os eventos cadastrados.
$router->get('/dashboard/agenda', [AgendaController::class, 'calendario'], [AuthMiddleware::class]);
$router->get('/dashboard/agenda/lista', [AgendaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/agenda/novo', [AgendaController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/agenda', [AgendaController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/agenda/{id}/editar', [AgendaController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/agenda/{id}', [AgendaController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/agenda/{id}/excluir', [AgendaController::class, 'destroy'], [AuthMiddleware::class]);

// Modulo Financeiro. Mesmo motivo: precisa vir antes do catch-all.
// PlanoMiddleware: Financeiro exige plano Plus ou superior (ver
// Igrejas\Models\Plano). As rotas de categorias precisam ser
// registradas antes de "/dashboard/financeiro/{id}" (POST) - senao o
// router (que casa por ordem de registro) tentaria interpretar
// "categorias" como um {id} de lancamento.
$router->get('/dashboard/financeiro', [FinanceiroController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/financeiro/novo', [FinanceiroController::class, 'create'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Consulta (sem alterar) do plano contratado, pra quem tem acesso ao
// Financeiro mas nao e admin - ver FinanceiroController::plano(). Sem
// PlanoMiddleware de proposito, mesmo motivo das rotas de
// Configuracoes: precisa continuar acessivel mesmo num plano que
// bloquearia o resto do Financeiro.
$router->get('/dashboard/financeiro/plano', [FinanceiroController::class, 'plano'], [AuthMiddleware::class]);
$router->post('/dashboard/financeiro', [FinanceiroController::class, 'store'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/financeiro/categorias', [FinanceiroController::class, 'categorias'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/financeiro/categorias', [FinanceiroController::class, 'storeCategoria'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/financeiro/categorias/{id}/status', [FinanceiroController::class, 'alternarStatusCategoria'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/financeiro/categorias/{id}/excluir', [FinanceiroController::class, 'destroyCategoria'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/financeiro/{id}/editar', [FinanceiroController::class, 'edit'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/financeiro/{id}', [FinanceiroController::class, 'update'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/financeiro/{id}/excluir', [FinanceiroController::class, 'destroy'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Modulo Projecao/Telao: painel do operador (protegido). Mesmo motivo:
// precisa vir antes do catch-all.
$router->get('/dashboard/projecao', [ProjecaoController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/projecao/iniciar', [ProjecaoController::class, 'iniciar'], [AuthMiddleware::class]);
$router->post('/dashboard/projecao/encerrar', [ProjecaoController::class, 'encerrar'], [AuthMiddleware::class]);
$router->post('/dashboard/projecao/imagens', [ProjecaoController::class, 'enviarImagem'], [AuthMiddleware::class]);
$router->post('/dashboard/projecao/imagens/{id}/favoritar', [ProjecaoController::class, 'favoritarImagem'], [AuthMiddleware::class]);
$router->post('/dashboard/projecao/imagens/{id}/excluir', [ProjecaoController::class, 'excluirImagem'], [AuthMiddleware::class]);

// Modulo Usuarios. Mesmo motivo: precisa vir antes do catch-all. Sem
// PlanoMiddleware - disponivel em todos os planos, mas so 'admin'
// acessa de verdade (ver AuthMiddleware::bloquearSePermissaoNegada e
// User::MODULOS_SOMENTE_ADMIN).
$router->get('/dashboard/usuarios', [UsuarioController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/usuarios/novo', [UsuarioController::class, 'create'], [AuthMiddleware::class]);
$router->post('/dashboard/usuarios', [UsuarioController::class, 'store'], [AuthMiddleware::class]);
$router->get('/dashboard/usuarios/{id}/editar', [UsuarioController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/dashboard/usuarios/{id}', [UsuarioController::class, 'update'], [AuthMiddleware::class]);
$router->post('/dashboard/usuarios/{id}/excluir', [UsuarioController::class, 'destroy'], [AuthMiddleware::class]);

// Modulo Permissoes. Mesmo motivo: precisa vir antes do catch-all.
// PlanoMiddleware: Permissoes exige o plano Premium (topo, ver
// Igrejas\Models\Plano) - so 'admin' acessa de verdade (mesma regra
// de Usuarios acima).
$router->get('/dashboard/permissoes', [PermissaoController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/permissoes/{id}/editar', [PermissaoController::class, 'edit'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/permissoes/{id}', [PermissaoController::class, 'update'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Configuracoes (logo da igreja, usada no fadeout da projecao, e o plano
// contratado). Mesmo motivo: precisa vir antes do catch-all. Sem
// PlanoMiddleware de proposito - precisa continuar acessivel mesmo se a
// igreja estiver num plano que bloquearia outros modulos, senao nao
// haveria como ver/trocar o plano pela propria interface.
$router->get('/dashboard/configuracoes', [ConfiguracaoController::class, 'index'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/logo', [ConfiguracaoController::class, 'atualizarLogo'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/logo/remover', [ConfiguracaoController::class, 'removerLogo'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/cadastro-membros', [ConfiguracaoController::class, 'atualizarCadastroMembros'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/chave-pix', [ConfiguracaoController::class, 'atualizarChavePix'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/mensagem-pix', [ConfiguracaoController::class, 'atualizarMensagemPix'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/mensagem-aniversario', [ConfiguracaoController::class, 'atualizarMensagemAniversario'], [AuthMiddleware::class]);
$router->post('/dashboard/configuracoes/permissoes-padrao', [ConfiguracaoController::class, 'atualizarPermissoesPadrao'], [AuthMiddleware::class]);

// Assinatura recorrente do plano via Mercado Pago (Checkout Pro). Sem
// PlanoMiddleware pelo mesmo motivo das rotas de Configuracoes acima.
$router->post('/dashboard/configuracoes/assinatura/{plano}', [AssinaturaController::class, 'iniciar'], [AuthMiddleware::class]);
$router->get('/dashboard/assinatura/retorno', [AssinaturaController::class, 'retorno'], [AuthMiddleware::class]);

// Tela de QR code de um upgrade proporcional pendente (ver
// AssinaturaController::iniciarUpgradeProporcional) - diferente da
// fatura-vencida abaixo, o acesso NAO fica bloqueado enquanto isso.
$router->get('/dashboard/configuracoes/upgrade-pendente', [AssinaturaController::class, 'upgradePendente'], [AuthMiddleware::class]);
$router->get('/dashboard/configuracoes/upgrade-pendente/status', [AssinaturaController::class, 'upgradePendenteStatus'], [AuthMiddleware::class]);

// Tela exibida quando um modulo fora do plano contratado e acessado (ver
// PlanoMiddleware). Tambem sem PlanoMiddleware, para nao criar um loop de
// redirecionamento.
$router->get('/dashboard/plano-bloqueado', [DashboardController::class, 'planoBloqueado'], [AuthMiddleware::class]);

// Tela exibida quando a fatura Pix de renovacao mensal vence sem
// pagamento (ver AuthMiddleware) - mostra o QR code pra pagar e libera o
// acesso de volta assim que o webhook confirmar.
$router->get('/dashboard/fatura-vencida', [ConfiguracaoController::class, 'faturaVencida'], [AuthMiddleware::class]);
$router->get('/dashboard/fatura-vencida/status', [ConfiguracaoController::class, 'faturaVencidaStatus'], [AuthMiddleware::class]);

// Rota explicita (fora do catch-all "/dashboard/{slug}" abaixo), sem
// PlanoMiddleware de proposito - assim como Configuracoes, o historico de
// cobrancas precisa continuar acessivel mesmo se o modulo estiver fora do
// plano contratado. Acesso (admin, ou quem o admin liberar) e resolvido
// por User::podeAcessarModulo(), ja aplicado em toda rota de dashboard via
// AuthMiddleware::bloquearSePermissaoNegada.
$router->get('/dashboard/faturas', [FaturaController::class, 'index'], [AuthMiddleware::class]);

// Tela exibida quando o teste gratis de 7 dias vence sem a igreja
// escolher um plano pago (ver AuthMiddleware).
$router->get('/dashboard/trial-expirado', [ConfiguracaoController::class, 'trialExpirado'], [AuthMiddleware::class]);

// Tela exibida quando um usuario (papel 'usuario') tenta acessar um
// modulo fora da propria permissao (ver
// AuthMiddleware::bloquearSePermissaoNegada). Tambem sem PlanoMiddleware,
// pelo mesmo motivo de plano-bloqueado acima.
$router->get('/dashboard/sem-permissao', [DashboardController::class, 'semPermissao'], [AuthMiddleware::class]);

// Modulo Patrimonio. Mesmo motivo: precisa vir antes do catch-all.
// PlanoMiddleware: Patrimonio exige o plano Premium (topo, ver
// Igrejas\Models\Plano).
$router->get('/dashboard/patrimonio', [PatrimonioController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/patrimonio/novo', [PatrimonioController::class, 'create'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/patrimonio', [PatrimonioController::class, 'store'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/patrimonio/{id}/editar', [PatrimonioController::class, 'edit'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/patrimonio/{id}', [PatrimonioController::class, 'update'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/patrimonio/{id}/excluir', [PatrimonioController::class, 'destroy'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Modulo Comunicacao. Mesmo motivo: precisa vir antes do catch-all.
// PlanoMiddleware: Comunicacao exige plano Plus ou superior (ver
// Igrejas\Models\Plano).
$router->get('/dashboard/comunicacao', [ComunicacaoController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/comunicacao/novo', [ComunicacaoController::class, 'create'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/comunicacao', [ComunicacaoController::class, 'store'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->get('/dashboard/comunicacao/{id}/editar', [ComunicacaoController::class, 'edit'], [AuthMiddleware::class, PlanoMiddleware::class]);
// "show" (pagina de detalhe do aviso) precisa vir depois de "novo"
// acima - senao "novo" seria interpretado como um {id}, ja que o
// router casa por ordem de registro (ver Igrejas\Core\Router).
$router->get('/dashboard/comunicacao/{id}', [ComunicacaoController::class, 'show'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/comunicacao/{id}', [ComunicacaoController::class, 'update'], [AuthMiddleware::class, PlanoMiddleware::class]);
$router->post('/dashboard/comunicacao/{id}/excluir', [ComunicacaoController::class, 'destroy'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Modulo Relatorios. Mesmo motivo: precisa vir antes do catch-all.
// Somente leitura (sem create/edit/delete). PlanoMiddleware: Relatorios
// exige o plano Premium (topo, ver Igrejas\Models\Plano).
$router->get('/dashboard/relatorios', [RelatorioController::class, 'index'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Catch-all dos demais slugs do menu que ainda nao tem controller
// proprio. PlanoMiddleware aqui cobre todos eles de uma vez so, com
// base no slug da propria URI.
$router->get('/dashboard/{slug}', [DashboardController::class, 'page'], [AuthMiddleware::class, PlanoMiddleware::class]);

// Tela publica do telao (projetor). Acesso direto por link com token, ou
// por PIN de 6 digitos (mais facil de digitar num controle de TV) que
// redireciona pro link com token - sem exigir login administrativo.
$router->get('/telao', [TelaoController::class, 'entrar']);
$router->post('/telao', [TelaoController::class, 'autenticar']);
$router->get('/telao/{token}', [TelaoController::class, 'show']);

// Quadro de avisos publico da igreja (sem login) - link pra compartilhar
// com a congregacao (grupo do WhatsApp, QR code no templo, etc.). Mostra
// so os avisos de Comunicacao publicados com publico "todos".
$router->get('/avisos', [AvisoPublicoController::class, 'index']);

// Doacao publica via Pix estatico (chave da propria igreja, sem login)
// - link pra compartilhar com a congregacao, mesmo padrao do quadro de
// avisos acima. Ver DoacaoController pro porque nao ha confirmacao
// automatica de pagamento aqui.
$router->get('/doar', [DoacaoController::class, 'formulario']);
$router->post('/doar', [DoacaoController::class, 'criar']);
$router->get('/doar/{id}', [DoacaoController::class, 'mostrar']);
$router->post('/doar/{id}/confirmar', [DoacaoController::class, 'confirmar']);

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
$router->post('/projecao/{token}/biblia/ler', [ProjecaoEstadoController::class, 'lerBiblia']);
$router->post('/projecao/{token}/video', [ProjecaoEstadoController::class, 'definirVideo']);
$router->post('/projecao/{token}/video/estado', [ProjecaoEstadoController::class, 'definirEstadoVideo']);
$router->post('/projecao/{token}/video/volume', [ProjecaoEstadoController::class, 'definirVolumeVideo']);
$router->post('/projecao/{token}/video/mudo', [ProjecaoEstadoController::class, 'alternarMudoVideo']);
$router->post('/projecao/{token}/video/reiniciar', [ProjecaoEstadoController::class, 'reiniciarVideo']);
$router->post('/projecao/{token}/video/tempo', [ProjecaoEstadoController::class, 'atualizarTempoVideo']);
$router->post('/projecao/{token}/logo', [ProjecaoEstadoController::class, 'mostrarLogo']);
$router->post('/projecao/{token}/limpar', [ProjecaoEstadoController::class, 'limpar']);
$router->post('/projecao/{token}/pix', [ProjecaoEstadoController::class, 'mostrarPix']);
$router->post('/projecao/{token}/imagem', [ProjecaoEstadoController::class, 'mostrarImagem']);

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
$router->get('/plataforma/avisos', [PlataformaController::class, 'avisos'], [PlataformaAuthMiddleware::class]);
$router->post('/plataforma/avisos', [PlataformaController::class, 'publicarAviso'], [PlataformaAuthMiddleware::class]);
$router->post('/plataforma/avisos/{id}/encerrar', [PlataformaController::class, 'encerrarAviso'], [PlataformaAuthMiddleware::class]);
