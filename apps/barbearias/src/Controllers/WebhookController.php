<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Controller;
use Barbearias\Core\MercadoPagoClient;
use Barbearias\Models\Barbearia;
use Barbearias\Models\FaturaBarbearia;

/**
 * Notificacoes assincronas do Mercado Pago. Bem mais simples que o
 * equivalente em Igrejas\Controllers\AssinaturaController::webhook():
 * como o Barbearias so tem UMA barbearia (nao um "central" + N bancos
 * isolados), confirmar um pagamento e sempre um UPDATE direto na mesma
 * tabela - nao existe provisionamento nem banco por tenant pra tocar.
 */
final class WebhookController extends Controller
{
    public function mercadoPago(): void
    {
        $corpoBruto = (string) (file_get_contents('php://input') ?: '');
        $headers = $this->cabecalhosEmMinusculo();
        $xSignature = $headers['x-signature'] ?? '';
        $xRequestId = $headers['x-request-id'] ?? '';
        $dataId = $this->extrairDataId($corpoBruto);
        $tipo = $this->extrairTipo($corpoBruto);

        $mp = new MercadoPagoClient();

        if (!$mp->validarAssinaturaWebhook($xSignature, $xRequestId, $dataId)) {
            http_response_code(401);

            return;
        }

        if ($tipo === 'payment') {
            $this->processarPagamento($mp, $dataId);

            return;
        }

        $this->processarAssinatura($mp, $dataId);
    }

    /**
     * Cobranca Pix avulsa (cadastro novo ou renovacao mensal - ver
     * cron/gerar_faturas_pix.php). "data.id" aqui e um id de pagamento.
     */
    private function processarPagamento(MercadoPagoClient $mp, string $paymentId): void
    {
        try {
            $resposta = $mp->buscarPagamento($paymentId);
        } catch (\RuntimeException) {
            // Falha de rede: responde erro de proposito, pro Mercado Pago
            // reenviar esta notificacao mais tarde.
            http_response_code(502);

            return;
        }

        if ($resposta['status'] !== 200) {
            http_response_code(200);

            return;
        }

        $statusPagamento = (string) ($resposta['body']['status'] ?? '');
        $statusInterno = match ($statusPagamento) {
            'approved' => 'autorizada',
            'cancelled', 'rejected', 'refunded', 'charged_back' => 'cancelada',
            default => 'pendente',
        };

        $fatura = FaturaBarbearia::buscarPorPaymentId($paymentId);

        if ($fatura !== null && $statusInterno === 'autorizada' && $fatura->status === FaturaBarbearia::STATUS_PENDENTE) {
            FaturaBarbearia::marcarPaga($fatura->id);
            Barbearia::marcarAtiva($fatura->barbeariaId);
            Barbearia::atualizarProximoVencimento($fatura->barbeariaId, new \DateTimeImmutable('+30 days'));
            // Cobre o caso de reativacao apos o ciclo cancelado ja ter
            // terminado (barbearia suspensa que gerou uma cobranca nova
            // manualmente na tela /dashboard/assinatura) - sem isso o
            // cron suspender_assinaturas_canceladas.php suspenderia de
            // novo no proximo vencimento, mesmo com o pagamento em dia.
            Barbearia::cancelarCancelamento($fatura->barbeariaId);
        }

        http_response_code(200);
    }

    /**
     * Assinatura recorrente por cartao (Checkout Pro/preapproval).
     * "data.id" aqui e o id da assinatura - nunca confia no corpo da
     * notificacao pra decidir o status, sempre busca o estado
     * autoritativo direto na API.
     */
    private function processarAssinatura(MercadoPagoClient $mp, string $preapprovalId): void
    {
        try {
            $resposta = $mp->buscarAssinatura($preapprovalId);
        } catch (\RuntimeException) {
            http_response_code(502);

            return;
        }

        if ($resposta['status'] !== 200) {
            // Nao era notificacao de assinatura - por exemplo, o aviso de
            // um pagamento avulso da cobranca recorrente. Ignora sem erro.
            http_response_code(200);

            return;
        }

        $statusMp = (string) ($resposta['body']['status'] ?? '');
        $statusInterno = match ($statusMp) {
            'authorized' => 'autorizada',
            'paused' => 'pausada',
            'cancelled' => 'cancelada',
            default => 'pendente',
        };

        $barbearia = Barbearia::buscarPorMpPreapprovalId($preapprovalId);

        if ($barbearia !== null && $statusInterno === 'autorizada') {
            Barbearia::marcarAtiva($barbearia->id);
            Barbearia::atualizarProximoVencimento($barbearia->id, new \DateTimeImmutable('+30 days'));
            Barbearia::cancelarCancelamento($barbearia->id);
        }

        http_response_code(200);
    }

    /** @return array<string, string> */
    private function cabecalhosEmMinusculo(): array
    {
        $headers = [];

        foreach (getallheaders() ?: [] as $chave => $valor) {
            $headers[strtolower($chave)] = $valor;
        }

        return $headers;
    }

    /**
     * O parametro "data.id" da URL de notificacao vem com um ponto
     * literal no nome - o PHP converte pontos em nomes de parametro pra
     * underscore ao popular $_GET, entao precisa ser lido direto da
     * query string bruta. Cai pro corpo (JSON) como reforco, caso a
     * query string nao traga o parametro.
     */
    private function extrairDataId(string $corpoBruto): string
    {
        $query = $_SERVER['QUERY_STRING'] ?? '';

        if (preg_match('/(?:^|&)data\.id=([^&]+)/', $query, $matches) === 1) {
            return urldecode($matches[1]);
        }

        $corpo = json_decode($corpoBruto, true);

        return (string) ($corpo['data']['id'] ?? '');
    }

    /**
     * O tipo da notificacao ("payment" ou "subscription_preapproval")
     * vem tanto na query string ("type"/"topic", dependendo da versao do
     * webhook) quanto, as vezes, no corpo.
     */
    private function extrairTipo(string $corpoBruto): string
    {
        $tipo = (string) ($_GET['type'] ?? $_GET['topic'] ?? '');

        if ($tipo !== '') {
            return $tipo;
        }

        $corpo = json_decode($corpoBruto, true);

        return (string) ($corpo['type'] ?? $corpo['topic'] ?? '');
    }
}
