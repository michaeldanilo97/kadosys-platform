<?php

declare(strict_types=1);

namespace Food\Core\Middleware;

use Food\Core\Auth;
use Food\Core\MiddlewareInterface;
use Food\Core\Request;
use Food\Models\Restaurante;
use Food\Models\FaturaRestaurante;

/**
 * Garante que apenas usuarios autenticados acessem rotas protegidas
 * (ex.: /dashboard e suas subpaginas). Visitantes sao redirecionados
 * para a tela de login.
 *
 * Tambem bloqueia o acesso de restaurantes em teste gratis cujo prazo ja
 * passou, e de restaurantes que pagam por Pix cuja ultima fatura venceu
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
        $restaurante = $usuario !== null ? Restaurante::find($usuario->restauranteId) : null;

        if ($restaurante === null || !$this->pagamentoPendente($restaurante)) {
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
    private function pagamentoPendente(Restaurante $restaurante): bool
    {
        if ($restaurante->status === Restaurante::STATUS_SUSPENSA) {
            return true;
        }

        if ($restaurante->status === Restaurante::STATUS_PENDENTE) {
            // Cadastro por Pix ou cartao ainda aguardando a confirmacao
            // do primeiro pagamento (ver Food\Controllers\WebhookController).
            return true;
        }

        if ($restaurante->metodoPagamento === 'trial') {
            return $restaurante->trialExpiraEm !== null
                && new \DateTimeImmutable() > new \DateTimeImmutable($restaurante->trialExpiraEm);
        }

        if ($restaurante->metodoPagamento === 'pix') {
            $fatura = FaturaRestaurante::ultimaDaRestaurante($restaurante->id);

            if ($fatura === null || $fatura->status === FaturaRestaurante::STATUS_PAGA || $fatura->status === FaturaRestaurante::STATUS_CANCELADA) {
                return false;
            }

            // 'expirada' ja significa vencida sem pagamento (ver
            // FaturaRestaurante::marcarExpiradasVencidas, chamada pelo
            // cron). Uma fatura 'pendente' so bloqueia depois que a
            // propria data de vencimento passar.
            if ($fatura->status === FaturaRestaurante::STATUS_PENDENTE && new \DateTimeImmutable() <= new \DateTimeImmutable($fatura->vencimento)) {
                return false;
            }

            return true;
        }

        // Cartao: a cobranca recorrente e feita pelo proprio Mercado
        // Pago via preapproval - se falhar/for cancelada, o unico sinal
        // que temos e o status do proprio restaurante virar 'suspenso'
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
