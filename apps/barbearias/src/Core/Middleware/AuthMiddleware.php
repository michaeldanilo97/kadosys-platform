<?php

declare(strict_types=1);

namespace Barbearias\Core\Middleware;

use Barbearias\Core\Auth;
use Barbearias\Core\MiddlewareInterface;
use Barbearias\Core\Request;
use Barbearias\Models\Barbearia;
use Barbearias\Models\FaturaBarbearia;

/**
 * Garante que apenas usuarios autenticados acessem rotas protegidas
 * (ex.: /dashboard e suas subpaginas). Visitantes sao redirecionados
 * para a tela de login.
 *
 * Tambem bloqueia o acesso de barbearias em teste gratis cujo prazo ja
 * passou, e de barbearias que pagam por Pix cuja ultima fatura venceu
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

        // Sem essa excecao o proprio usuario ficaria preso sem conseguir
        // ver a tela de assinatura pra pagar, ou sem conseguir sair da conta.
        if (str_starts_with($uri, '/dashboard/assinatura') || $uri === '/logout') {
            return;
        }

        $usuario = $auth->user();
        $barbearia = $usuario !== null ? Barbearia::find($usuario->barbeariaId) : null;

        if ($barbearia === null || !$this->pagamentoPendente($barbearia)) {
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
    private function pagamentoPendente(Barbearia $barbearia): bool
    {
        if ($barbearia->status === Barbearia::STATUS_SUSPENSA) {
            return true;
        }

        if ($barbearia->status === Barbearia::STATUS_PENDENTE) {
            // Cadastro por Pix ou cartao ainda aguardando a confirmacao
            // do primeiro pagamento (ver Barbearias\Controllers\WebhookController).
            return true;
        }

        if ($barbearia->metodoPagamento === 'trial') {
            return $barbearia->trialExpiraEm !== null
                && new \DateTimeImmutable() > new \DateTimeImmutable($barbearia->trialExpiraEm);
        }

        if ($barbearia->metodoPagamento === 'pix') {
            $fatura = FaturaBarbearia::ultimaDaBarbearia($barbearia->id);

            if ($fatura === null || $fatura->status === FaturaBarbearia::STATUS_PAGA || $fatura->status === FaturaBarbearia::STATUS_CANCELADA) {
                return false;
            }

            // 'expirada' ja significa vencida sem pagamento (ver
            // FaturaBarbearia::marcarExpiradasVencidas, chamada pelo
            // cron). Uma fatura 'pendente' so bloqueia depois que a
            // propria data de vencimento passar.
            if ($fatura->status === FaturaBarbearia::STATUS_PENDENTE && new \DateTimeImmutable() <= new \DateTimeImmutable($fatura->vencimento)) {
                return false;
            }

            return true;
        }

        // Cartao: a cobranca recorrente e feita pelo proprio Mercado
        // Pago via preapproval - se falhar/for cancelada, o unico sinal
        // que temos e o status da propria barbearia virar 'suspenso'
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
