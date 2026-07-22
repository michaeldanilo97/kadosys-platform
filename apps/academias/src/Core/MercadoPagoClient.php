<?php

declare(strict_types=1);

namespace Academias\Core;

/**
 * Cliente minimalista da API REST do Mercado Pago (Checkout Pro +
 * Assinaturas/preapproval + Pix avulso), via curl puro - sem SDK
 * oficial, mesmo padrao usado em Igrejas\Core\MercadoPagoClient (mesma
 * conta Mercado Pago, so muda o valor/plano cobrado).
 *
 * Documentacao: https://www.mercadopago.com.br/developers
 */
final class MercadoPagoClient
{
    private const API_BASE = 'https://api.mercadopago.com';

    private string $accessToken;
    private string $webhookSecret;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/mercadopago.php';
        $this->accessToken = $config['access_token'];
        $this->webhookSecret = $config['webhook_secret'];
    }

    public function configurado(): bool
    {
        return $this->accessToken !== '';
    }

    /**
     * Cria uma assinatura recorrente mensal (preapproval) e retorna a
     * resposta crua da API (inclui "id" e "init_point", a URL de
     * checkout pra onde o admin deve ser redirecionado).
     *
     * @param array{reason:string, amount:float, payerEmail:string, backUrl:string, externalReference:string} $dados
     * @return array{status:int, body:array}
     */
    public function criarAssinatura(array $dados): array
    {
        return $this->request('POST', '/preapproval', [
            'reason' => $dados['reason'],
            'auto_recurring' => [
                'frequency' => 1,
                'frequency_type' => 'months',
                'transaction_amount' => $dados['amount'],
                'currency_id' => 'BRL',
            ],
            'back_url' => $dados['backUrl'],
            'payer_email' => $dados['payerEmail'],
            'external_reference' => $dados['externalReference'],
            'status' => 'pending',
        ]);
    }

    /**
     * @return array{status:int, body:array}
     */
    public function buscarAssinatura(string $preapprovalId): array
    {
        return $this->request('GET', '/preapproval/' . urlencode($preapprovalId));
    }

    /**
     * Atualiza o valor cobrado a cada ciclo de uma assinatura de cartao
     * ja autorizada.
     *
     * @return array{status:int, body:array}
     */
    public function atualizarAssinatura(string $preapprovalId, float $novoValor): array
    {
        return $this->request('PUT', '/preapproval/' . urlencode($preapprovalId), [
            'auto_recurring' => [
                'transaction_amount' => $novoValor,
            ],
        ]);
    }

    /**
     * Pausa uma assinatura recorrente de cartao - o Mercado Pago para de
     * tentar cobrar, mas (diferente de cancelar) o preapproval continua
     * podendo ser retomado depois via reativarAssinatura(), sem o
     * cliente precisar passar pelo checkout de novo. Usado no
     * cancelamento self-service (ver
     * Academias\Controllers\ConfiguracaoController::cancelarAssinatura).
     *
     * @return array{status:int, body:array}
     */
    public function pausarAssinatura(string $preapprovalId): array
    {
        return $this->request('PUT', '/preapproval/' . urlencode($preapprovalId), [
            'status' => 'paused',
        ]);
    }

    /**
     * Retoma uma assinatura pausada - volta a cobrar no proximo ciclo.
     *
     * @return array{status:int, body:array}
     */
    public function reativarAssinatura(string $preapprovalId): array
    {
        return $this->request('PUT', '/preapproval/' . urlencode($preapprovalId), [
            'status' => 'authorized',
        ]);
    }

    /**
     * Lista os pagamentos ja debitados (ou tentados) de uma assinatura
     * recorrente de cartao (preapproval) - o "extrato" da cobranca
     * automatica.
     *
     * @return array{status:int, body:array}
     */
    public function buscarPagamentosAssinatura(string $preapprovalId): array
    {
        return $this->request(
            'GET',
            '/authorized_payments/search?preapproval_id=' . urlencode($preapprovalId) . '&sort=date_created&criteria=desc'
        );
    }

    /**
     * Cria uma cobranca Pix avulsa (QR code + copia-e-cola), com prazo
     * de vencimento. Diferente de criarAssinatura() (cartao recorrente,
     * debitado automaticamente), aqui cada cobranca e paga manualmente
     * pelo cliente - a renovacao mensal fica por conta de um cron (ver
     * cron/gerar_faturas_pix.php) que gera uma cobranca nova a cada
     * ciclo.
     *
     * @param array{description:string, amount:float, payerEmail:string, externalReference:string, expiraEm:\DateTimeImmutable} $dados
     * @return array{status:int, body:array}
     */
    public function criarPagamentoPix(array $dados): array
    {
        return $this->request('POST', '/v1/payments', [
            'transaction_amount' => $dados['amount'],
            'description' => $dados['description'],
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => $dados['payerEmail'],
            ],
            'external_reference' => $dados['externalReference'],
            // O Mercado Pago exige milissegundos nesse campo
            // (yyyy-MM-dd'T'HH:mm:ss.SSSzzz) - sem eles a API recusa com
            // "must be valid date and format" (erro 400, code 23).
            'date_of_expiration' => $dados['expiraEm']->format('Y-m-d\TH:i:s.000P'),
        ], [
            // Evita que uma cobranca seja criada em dobro se a
            // requisicao falhar depois de ja ter chegado no Mercado
            // Pago (ex.: timeout na resposta) e o codigo tentar de novo.
            'X-Idempotency-Key: ' . $dados['externalReference'],
        ]);
    }

    /**
     * @return array{status:int, body:array}
     */
    public function buscarPagamento(string $paymentId): array
    {
        return $this->request('GET', '/v1/payments/' . urlencode($paymentId));
    }

    /**
     * Valida o cabecalho "x-signature" de uma notificacao de webhook do
     * Mercado Pago.
     *
     * Formato do cabecalho: "ts=<timestamp_ms>,v1=<hash_hex>". O hash e
     * um HMAC-SHA256 (hex) do manifest "id:{id};request-id:{request-id};
     * ts:{ts};" usando o webhook secret como chave, onde {id} e o valor
     * do parametro "data.id" da propria URL de notificacao (nao do
     * corpo) em minusculas quando alfanumerico, e {request-id} vem do
     * cabecalho "x-request-id".
     */
    public function validarAssinaturaWebhook(string $xSignature, string $xRequestId, string $dataId): bool
    {
        if ($this->webhookSecret === '' || $xSignature === '' || $dataId === '') {
            return false;
        }

        $partes = [];

        foreach (explode(',', $xSignature) as $parte) {
            $par = array_pad(explode('=', trim($parte), 2), 2, '');
            $partes[trim($par[0])] = trim($par[1]);
        }

        $ts = $partes['ts'] ?? '';
        $v1 = $partes['v1'] ?? '';

        if ($ts === '' || $v1 === '') {
            return false;
        }

        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', mb_strtolower($dataId), $xRequestId, $ts);
        $esperado = hash_hmac('sha256', $manifest, $this->webhookSecret);

        return hash_equals($esperado, $v1);
    }

    /**
     * @param array<int, string> $headersExtras
     * @return array{status:int, body:array}
     */
    private function request(string $method, string $path, ?array $body = null, array $headersExtras = []): array
    {
        $ch = curl_init(self::API_BASE . $path);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge([
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ], $headersExtras),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $resposta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro = curl_error($ch);
        curl_close($ch);

        if ($resposta === false) {
            throw new \RuntimeException('Falha ao comunicar com o Mercado Pago: ' . $erro);
        }

        $dados = json_decode((string) $resposta, true);

        return [
            'status' => $status,
            'body' => is_array($dados) ? $dados : [],
        ];
    }
}
