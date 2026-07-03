<?php

declare(strict_types=1);

namespace Igrejas\Core\Middleware;

use Igrejas\Core\Auth;
use Igrejas\Core\MiddlewareInterface;
use Igrejas\Core\Request;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\FaturaPix;

/**
 * Garante que apenas usuarios autenticados acessem rotas protegidas
 * (ex.: /dashboard e suas subpaginas). Visitantes sao redirecionados
 * para a tela de login.
 *
 * Tambem bloqueia o acesso de igrejas que pagam por Pix (ver
 * plataforma_faturas) quando a ultima fatura de renovacao venceu sem
 * pagamento, e de igrejas em teste gratis cujo prazo ja passou sem
 * escolherem um plano pago - unico ponto de entrada comum a todas as
 * rotas do dashboard, entao e o lugar mais simples de aplicar esses
 * bloqueios sem repetir a middleware em cada rota.
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
        $this->bloquearSeTrialExpirado($request);
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
        if ($uri === '/dashboard/trial-expirado' || $uri === '/logout' || str_starts_with($uri, '/dashboard/configuracoes')) {
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
