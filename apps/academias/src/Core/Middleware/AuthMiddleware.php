<?php

declare(strict_types=1);

namespace Academias\Core\Middleware;

use Academias\Core\Auth;
use Academias\Core\MiddlewareInterface;
use Academias\Core\Request;
use Academias\Models\Academia;
use Academias\Models\FaturaAcademia;

/**
 * Garante que apenas usuarios autenticados acessem rotas protegidas
 * (ex.: /dashboard e suas subpaginas). Visitantes sao redirecionados
 * para a tela de login.
 *
 * Tambem bloqueia o acesso de academias em teste gratis cujo prazo ja
 * passou, e de academias que pagam por Pix cuja ultima fatura venceu
 * sem pagamento - equivalente (bem mais simples, por nao ter um modulo
 * de permissoes por usuario ainda) ao
 * Igrejas\Core\Middleware\AuthMiddleware.
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

        $this->bloquearSePagamentoPendente($request, $auth);
    }

    private function bloquearSePagamentoPendente(Request $request, Auth $auth): void
    {
        $uri = $this->uriSemBase($request);

        // Sem essas excecoes o proprio usuario ficaria preso sem conseguir
        // ver a tela de assinatura pra pagar, sem conseguir ver o
        // historico/status das proprias faturas, ou sem conseguir sair
        // da conta.
        if (str_starts_with($uri, '/dashboard/assinatura') || str_starts_with($uri, '/dashboard/faturas') || $uri === '/logout') {
            return;
        }

        $usuario = $auth->user();
        $academia = $usuario !== null ? Academia::find($usuario->academiaId) : null;

        if ($academia === null || !$this->pagamentoPendente($academia)) {
            return;
        }

        $base = $this->config['base_path'] ?? '';
        header('Location: ' . $base . '/dashboard/assinatura');
        exit;
    }

    /**
     * So a decisao (sem side-effect de header/exit), separada pra poder
     * ser testada isoladamente.
     */
    private function pagamentoPendente(Academia $academia): bool
    {
        if ($academia->status === Academia::STATUS_SUSPENSA) {
            return true;
        }

        if ($academia->status === Academia::STATUS_PENDENTE) {
            // Cadastro por Pix ou cartao ainda aguardando a confirmacao
            // do primeiro pagamento (ver Academias\Controllers\WebhookController).
            return true;
        }

        if ($academia->metodoPagamento === 'trial') {
            return $academia->trialExpiraEm !== null
                && new \DateTimeImmutable() > new \DateTimeImmutable($academia->trialExpiraEm);
        }

        if ($academia->metodoPagamento === 'pix') {
            $fatura = FaturaAcademia::ultimaDaAcademia($academia->id);

            if ($fatura === null || $fatura->status === FaturaAcademia::STATUS_PAGA || $fatura->status === FaturaAcademia::STATUS_CANCELADA) {
                return false;
            }

            // 'expirada' ja significa vencida sem pagamento (ver
            // FaturaAcademia::marcarExpiradasVencidas, chamada pelo
            // cron). Uma fatura 'pendente' so bloqueia depois que a
            // propria data de vencimento passar.
            if ($fatura->status === FaturaAcademia::STATUS_PENDENTE && new \DateTimeImmutable() <= new \DateTimeImmutable($fatura->vencimento)) {
                return false;
            }

            return true;
        }

        // Cartao: a cobranca recorrente e feita pelo proprio Mercado
        // Pago via preapproval - se falhar/for cancelada, o unico sinal
        // que temos e o status da propria academia virar 'suspenso'
        // (ja coberto acima), decidido manualmente por enquanto.
        return false;
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
