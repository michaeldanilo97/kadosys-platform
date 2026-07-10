<?php

declare(strict_types=1);

namespace Igrejas\Controllers;

use Igrejas\Core\Auth;
use Igrejas\Core\Controller;
use Igrejas\Core\MercadoPagoClient;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\Assinatura;
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
        $pagamentosCartao = $assinaturaCartao !== null
            ? $this->buscarPagamentosCartao($assinaturaCartao->mpPreapprovalId)
            : null;

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
            'pagamentosCartao' => $pagamentosCartao,
        ], 'dashboard');
    }

    /**
     * Extrato de cobrancas ja debitadas/tentadas da assinatura recorrente
     * de cartao, direto na API do Mercado Pago - nao temos essas cobrancas
     * gravadas no nosso banco (quem debita e renova sozinho e o proprio
     * Mercado Pago, sem passar pelo nosso webhook a cada ciclo, so na
     * autorizacao inicial). Retorna null (em vez de lista vazia) se a
     * consulta falhar ou o pagamento online nao estiver configurado, pra
     * a tela distinguir "sem cobranca nenhuma ainda" de "nao consegui
     * buscar agora" e cair de volta pro aviso simples.
     *
     * @return array<int, array{data:?string, valor:?float, status:string}>|null
     */
    private function buscarPagamentosCartao(string $preapprovalId): ?array
    {
        $mp = new MercadoPagoClient();

        if (!$mp->configurado()) {
            return null;
        }

        try {
            $resposta = $mp->buscarPagamentosAssinatura($preapprovalId);
        } catch (\RuntimeException $exception) {
            Assinatura::registrarEvento(null, 'erro_buscar_pagamentos_assinatura', $exception->getMessage());

            return null;
        }

        if ($resposta['status'] !== 200 || !isset($resposta['body']['results']) || !is_array($resposta['body']['results'])) {
            return null;
        }

        return array_map(
            static fn (array $pagamento): array => [
                'data' => $pagamento['date_created'] ?? $pagamento['date_approved'] ?? null,
                'valor' => isset($pagamento['transaction_amount']) ? (float) $pagamento['transaction_amount'] : null,
                'status' => (string) ($pagamento['status'] ?? 'desconhecido'),
            ],
            $resposta['body']['results']
        );
    }
}
