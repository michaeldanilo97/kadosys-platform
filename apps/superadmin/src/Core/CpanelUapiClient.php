<?php

declare(strict_types=1);

namespace Superadmin\Core;

/**
 * Cliente minimalista da UAPI do cPanel (curl puro, mesmo estilo do
 * Igrejas\Core\CpanelUapiClient) - usado aqui apenas para EXCLUIR banco
 * de dados/usuario MySQL de uma igreja removida via painel (o Super
 * Admin nao provisiona nada, so desprovisiona - ver Desprovisionador).
 *
 * Documentacao: https://api.docs.cpanel.net/cpanel/introduction/
 */
final class CpanelUapiClient
{
    private string $host;
    private string $port;
    private string $username;
    private string $apiToken;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/cpanel.php';
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->username = $config['username'];
        $this->apiToken = $config['api_token'];
    }

    public function configurado(): bool
    {
        return $this->host !== '' && $this->username !== '' && $this->apiToken !== '';
    }

    /**
     * O nome ja precisa vir com o prefixo da conta cPanel incluido (ex.:
     * "kadosys1_slugdaigreja"), mesmo padrao usado no provisionamento
     * original (Igrejas\Core\CpanelUapiClient::criarBancoDeDados).
     *
     * @return array{sucesso:bool, status_http:int, body:array}
     */
    public function excluirBancoDeDados(string $nome): array
    {
        return $this->chamar('Mysql', 'delete_database', ['name' => $nome]);
    }

    /**
     * @return array{sucesso:bool, status_http:int, body:array}
     */
    public function excluirUsuarioBanco(string $nome): array
    {
        return $this->chamar('Mysql', 'delete_user', ['name' => $nome]);
    }

    /**
     * @param array<string, string> $params
     * @return array{sucesso:bool, status_http:int, body:array}
     */
    private function chamar(string $modulo, string $funcao, array $params): array
    {
        $url = sprintf('https://%s:%s/execute/%s/%s', $this->host, $this->port, $modulo, $funcao);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => [
                'Authorization: cpanel ' . $this->username . ':' . $this->apiToken,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $resposta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro = curl_error($ch);
        curl_close($ch);

        if ($resposta === false) {
            throw new \RuntimeException("Falha ao comunicar com o cPanel ({$modulo}::{$funcao}): {$erro}");
        }

        $dados = json_decode($resposta, true);
        $dados = is_array($dados) ? $dados : [];

        $sucesso = $status === 200 && (int) ($dados['status'] ?? 0) === 1;

        return [
            'sucesso' => $sucesso,
            'status_http' => $status,
            'body' => $dados,
        ];
    }
}
