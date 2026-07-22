<?php

declare(strict_types=1);

namespace Igrejas\Core\Middleware;

use Igrejas\Core\Auth;
use Igrejas\Core\MiddlewareInterface;
use Igrejas\Core\Request;
use Igrejas\Core\Session;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\AgendaEvento;
use Igrejas\Models\AssinaturaTenant;
use Igrejas\Models\FaturaPix;
use Igrejas\Models\User;

/**
 * Garante que apenas usuarios autenticados acessem rotas protegidas
 * (ex.: /dashboard e suas subpaginas). Visitantes sao redirecionados
 * para a tela de login.
 *
 * Tambem bloqueia o acesso de igrejas que pagam por Pix (ver
 * plataforma_faturas) quando a ultima fatura de renovacao venceu sem
 * pagamento, de igrejas em teste gratis cujo prazo ja passou sem
 * escolherem um plano pago, e de usuarios (papel 'usuario', modulo
 * Usuarios e Permissoes) tentando acessar um modulo fora da propria
 * permissao - unico ponto de entrada comum a todas as rotas do
 * dashboard, entao e o lugar mais simples de aplicar esses bloqueios
 * sem repetir a middleware em cada rota.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function handle(Request $request): void
    {
        $auth = new Auth($this->config);

        if (!$auth->check()) {
            $base = $this->config['base_path'] ?? '';
            header('Location: ' . $base . '/login');
            exit;
        }

        $this->bloquearSeFaturaPixVencida($request);
        $this->bloquearSeAssinaturaCartaoNaoAutorizada($request);
        $this->bloquearSeTrialExpirado($request);
        $this->bloquearSePermissaoNegada($request, $auth);
    }

    private function bloquearSeFaturaPixVencida(Request $request): void
    {
        if (!$this->faturaPixVencida()) {
            return;
        }

        $base = $this->config['base_path'] ?? '';
        $uri = $this->uriSemBase($request);

        // Sem essas excecoes o proprio usuario ficaria preso sem conseguir
        // ver a fatura pra pagar, ou sem conseguir sair da conta.
        if ($uri === '/dashboard/fatura-vencida' || $uri === '/dashboard/fatura-vencida/status' || $uri === '/logout') {
            return;
        }

        header('Location: ' . $base . '/dashboard/fatura-vencida');
        exit;
    }

    /**
     * So a decisao (sem side-effect de header/exit), separada pra poder
     * ser testada isoladamente.
     */
    private function faturaPixVencida(): bool
    {
        $tenant = TenantResolver::atual();

        if ($tenant === null || $tenant->metodoPagamento !== 'pix') {
            return false;
        }

        $fatura = FaturaPix::ultimaDoTenant($tenant->id);

        if ($fatura === null || $fatura->status === 'paga' || $fatura->status === 'cancelada') {
            return false;
        }

        // 'expirada' ja significa vencida sem pagamento (ver
        // FaturaPix::marcarExpiradasVencidas, chamada pelo cron). Uma
        // fatura 'pendente' so bloqueia depois que a propria data de
        // vencimento passar - antes disso o admin so ve o aviso no topo
        // do painel (ver layouts/dashboard.php).
        if ($fatura->status === 'pendente' && new \DateTimeImmutable() <= new \DateTimeImmutable($fatura->vencimento)) {
            return false;
        }

        return true;
    }

    /**
     * Quem paga por cartao renova sozinho, direto no Mercado Pago via
     * preapproval - se essa cobranca recorrente para de ser autorizada
     * (cartao recusado, assinatura cancelada/pausada pelo proprio
     * Mercado Pago ou pelo banco emissor), nada detectava isso ate
     * agora: o tenant continuava com acesso liberado pra sempre, mesmo
     * sem pagar mais nada. Repara essa lacuna reaproveitando o registro
     * que o webhook ja mantem (ver AssinaturaController::
     * processarAssinaturaTenant) - sem mexer em Tenant::status, que e
     * usado por TenantResolver pra decidir se troca de banco (colocar
     * 'suspenso' la quebraria a resolucao do subdominio inteiro, nao so
     * o acesso ao dashboard).
     */
    private function bloquearSeAssinaturaCartaoNaoAutorizada(Request $request): void
    {
        if (!$this->assinaturaCartaoNaoAutorizada()) {
            return;
        }

        $uri = $this->uriSemBase($request);

        // Mesma excecao do trial expirado: precisa continuar acessando
        // Configuracoes pra poder assinar de novo, senao vira loop de
        // redirecionamento.
        if ($uri === '/logout' || str_starts_with($uri, '/dashboard/configuracoes')) {
            return;
        }

        Session::flash('config_errors', [
            'Sua assinatura por cartão foi cancelada ou está com o pagamento recusado. Assine novamente abaixo pra continuar usando o sistema.',
        ]);

        $base = $this->config['base_path'] ?? '';
        header('Location: ' . $base . '/dashboard/configuracoes');
        exit;
    }

    /**
     * So a decisao (sem side-effect de header/exit), separada pra poder
     * ser testada isoladamente.
     */
    private function assinaturaCartaoNaoAutorizada(): bool
    {
        $tenant = TenantResolver::atual();

        if ($tenant === null || $tenant->metodoPagamento !== 'cartao') {
            return false;
        }

        $assinatura = AssinaturaTenant::ultimaDoTenant($tenant->id);

        return $assinatura !== null && in_array($assinatura->status, ['pausada', 'cancelada'], true);
    }

    private function bloquearSeTrialExpirado(Request $request): void
    {
        if (!$this->trialExpirado()) {
            return;
        }

        $base = $this->config['base_path'] ?? '';
        $uri = $this->uriSemBase($request);

        // "/dashboard/configuracoes" (e as rotas dela, tipo o POST que
        // inicia a assinatura) precisa continuar acessivel mesmo
        // bloqueado - e la que a igreja escolhe um plano pago pra sair
        // do trial. Sem essa excecao viraria um loop de redirecionamento.
        // "/dashboard/financeiro/plano" e a mesma consulta de plano, so
        // que pra quem tem acesso ao Financeiro sem ser admin (ver
        // FinanceiroController::plano()) - precisa da mesma excecao.
        if (
            $uri === '/dashboard/trial-expirado'
            || $uri === '/logout'
            || str_starts_with($uri, '/dashboard/configuracoes')
            || $uri === '/dashboard/financeiro/plano'
        ) {
            return;
        }

        header('Location: ' . $base . '/dashboard/trial-expirado');
        exit;
    }

    /**
     * So a decisao (sem side-effect de header/exit), separada pra poder
     * ser testada isoladamente.
     */
    private function trialExpirado(): bool
    {
        $tenant = TenantResolver::atual();

        if ($tenant === null || $tenant->metodoPagamento !== 'trial' || $tenant->trialExpiraEm === null) {
            return false;
        }

        return new \DateTimeImmutable() > new \DateTimeImmutable($tenant->trialExpiraEm);
    }

    private function bloquearSePermissaoNegada(Request $request, Auth $auth): void
    {
        $uri = $this->uriSemBase($request);

        // "Meus filhos" (Kids > autoatendimento do responsavel) e igual
        // "perfil" abaixo: mesmo sem permissao administrativa no modulo
        // Kids, o proprio responsavel precisa conseguir gerenciar o PIN
        // dos filhos dele. KidsResponsavelController confere a posse
        // (responsavel_membro_id) por conta propria a cada acao, entao
        // nao depende da permissao de modulo concedida pela equipe.
        if (str_starts_with($uri, '/dashboard/kids/meus-filhos')) {
            return;
        }

        $slug = $this->moduloSlugDaUri($uri);

        if ($slug === null) {
            return;
        }

        // Telas do proprio sistema (nao modulos de verdade) - sempre
        // acessiveis, senao um usuario restrito ficaria preso sem
        // conseguir nem ver a propria tela de bloqueio. "perfil" entra
        // aqui tambem: e autoatendimento (ver PerfilController), todo
        // usuario precisa conseguir editar o PROPRIO perfil mesmo com
        // acesso restrito em Permissoes a outros modulos.
        if (in_array($slug, ['trial-expirado', 'fatura-vencida', 'plano-bloqueado', 'sem-permissao', 'perfil'], true)) {
            return;
        }

        // GET (ver a tela) so exige nivel "visualizar"; qualquer outro
        // metodo (POST, o unico usado pra escrita neste app) exige
        // nivel "editar" - ver User::podeAcessarModulo().
        $acaoMinima = $request->method === 'GET' ? User::NIVEL_VISUALIZAR : User::NIVEL_EDITAR;

        if (User::podeAcessarModulo($auth->user(), $slug, $acaoMinima)) {
            return;
        }

        // Excecao da Agenda: mesmo sem nivel "editar" no modulo, todo
        // usuario pode criar/editar/excluir um evento PRIVADO (visivel
        // so pra ele mesmo, ex.: um compromisso pessoal) que seja seu -
        // so evento PUBLICO (visivel pra todo mundo) continua exigindo
        // nivel "editar". Ver AgendaController::podeGerenciar(), que
        // repete essa mesma checagem de dono no controller.
        if ($slug === 'agenda' && $this->podeGerenciarEventoPrivado($request, $auth->user())) {
            return;
        }

        $base = $this->config['base_path'] ?? '';
        header('Location: ' . $base . '/dashboard/sem-permissao?modulo=' . urlencode($slug));
        exit;
    }

    private function podeGerenciarEventoPrivado(Request $request, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $uri = $this->uriSemBase($request);

        // Cadastrar evento novo: so libera se a propria pessoa esta
        // marcando o evento como privado - AgendaController::store()
        // sempre grava criado_por_user_id = usuario logado, entao um
        // evento privado criado aqui e automaticamente so dela.
        if ($uri === '/dashboard/agenda') {
            return $request->input('visibilidade') === 'privado';
        }

        // Editar/excluir um evento existente: so libera se ele ja e
        // privado e foi criado por essa mesma pessoa.
        if (preg_match('#^/dashboard/agenda/(\d+)#', $uri, $matches) === 1) {
            $evento = AgendaEvento::find((int) $matches[1]);

            return $evento !== null && $evento->ehPrivado() && $evento->criadoPorUserId === $user->id;
        }

        return false;
    }

    private function moduloSlugDaUri(string $uri): ?string
    {
        if (preg_match('#^/dashboard/([a-z\-]+)#', $uri, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function uriSemBase(Request $request): string
    {
        $base = $this->config['base_path'] ?? '';
        $uri = $request->uri;

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        return $uri;
    }
}
