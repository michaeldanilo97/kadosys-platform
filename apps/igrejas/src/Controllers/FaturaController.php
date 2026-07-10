<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\AssinaturaTenant;
use Igrejas\Models\FaturaPix;

/**
 * Tela de Faturas: historico de cobrancas (Pix e status da assinatura por
 * cartao) da igreja, pra admins (ou quem eles liberarem via Permissoes -
 * ver User::podeAcessarModulo()) acompanharem vencimento, pagamento e
 * pagarem uma fatura pendente sem precisar esperar o bloqueio automatico
 * (ver AuthMiddleware::bloquearSeFaturaPixVencida).
 *
 * So faz sentido pra igrejas provisionadas automaticamente (com registro
 * central de tenant, ver TenantResolver) - a instalacao original (sem
 * subdominio) nao gera fatura nenhuma aqui.
 */
final class FaturaController extends Controller
{
    public function index(): void
    {
        $tenant = TenantResolver::atual();
        $faturas = $tenant !== null ? FaturaPix::todasDoTenant($tenant->id) : [];
        $assinaturaCartao = $tenant !== null ? AssinaturaTenant::ultimaDoTenant($tenant->id) : null;

        // A fatura Pix pendente mais recente e a unica que da pra pagar
        // agora (o fluxo de pagamento sempre resolve "a ultima fatura do
        // tenant", ver ConfiguracaoController::faturaVencida()) - nao ha
        // como pagar uma fatura antiga isolada, entao so o topo da lista
        // ganha o botao quando pendente.
        $faturaPendenteId = null;

        foreach ($faturas as $fatura) {
            if ($fatura->status === 'pendente') {
                $faturaPendenteId = $fatura->id;
            }

            break;
        }

        echo $this->view('dashboard.faturas.index', [
            'pageTitle' => 'Faturas - KADOSYS Igrejas',
            'activeMenu' => 'faturas',
            'breadcrumb' => ['Dashboard', 'Faturas'],
            'user' => (new Auth($this->config))->user(),
            'modules' => DashboardController::modules(),
            'tenant' => $tenant,
            'faturas' => $faturas,
            'faturaPendenteId' => $faturaPendenteId,
            'assinaturaCartao' => $assinaturaCartao,
        ], 'dashboard');
    }
}
