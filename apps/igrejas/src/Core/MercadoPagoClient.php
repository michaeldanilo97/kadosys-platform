<?php

declare(strict_types=1);

namespace Igrejas\Core;

/**
 * Cliente minimalista da API REST do Mercado Pago (Checkout Pro +
 * Assinaturas/preapproval), via curl puro - sem SDK oficial, seguindo o
 * mesmo estilo "sem dependencias externas" do restante do projeto (o
 * host de producao tem allow_url_fopen desativado, entao curl e a unica
 * opcao viavel de qualquer forma).
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
     * @return array{status:int, body:array}
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init(self::API_BASE . $path);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ],
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
