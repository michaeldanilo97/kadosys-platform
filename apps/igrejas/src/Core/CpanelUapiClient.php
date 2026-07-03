<?php

declare(strict_types=1);

namespace Igrejas\Core;

/**
 * Cliente minimalista da UAPI do cPanel (curl puro, mesmo estilo do
 * MercadoPagoClient - sem SDK, sem dependencias externas), usado pelo
 * provisionamento automatico de uma nova igreja: criar banco de dados,
 * criar usuario MySQL, conceder privilegios, e criar o subdominio.
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
     * @return array{sucesso:bool, status_http:int, body:array}
     */
    public function criarBancoDeDados(string $nome): array
    {
        return $this->chamar('Mysql', 'create_database', ['name' => $nome]);
    }

    /**
     * @return array{sucesso:bool, status_http:int, body:array}
     */
    public function criarUsuarioBanco(string $nome, string $senha): array
    {
        return $this->chamar('Mysql', 'create_user', ['name' => $nome, 'password' => $senha]);
    }

    /**
     * A funcao "set_privileges_on_user" (nome usado na documentacao mais
     * recente da UAPI) nao existe nesta versao/instalacao de cPanel -
     * confirmado com um erro real em producao ("The system could not
     * find the function") e com uma listagem direta das funcoes do
     * modulo (Cpanel::API::Mysql) via SSH, que so tem
     * "set_privileges_on_database".
     *
     * @return array{sucesso:bool, status_http:int, body:array}
     */
    public function concederPrivilegios(string $usuario, string $banco): array
    {
        return $this->chamar('Mysql', 'set_privileges_on_database', [
            'user' => $usuario,
            'database' => $banco,
            'privileges' => 'ALL PRIVILEGES',
        ]);
    }

    /**
     * @return array{sucesso:bool, status_http:int, body:array}
     */
    public function criarSubdominio(string $subdominio, string $dominioRaiz, string $diretorio): array
    {
        return $this->chamar('SubDomain', 'addsubdomain', [
            'domain' => $subdominio,
            'rootdomain' => $dominioRaiz,
            'dir' => $diretorio,
        ]);
    }

    /**
     * Usado por Igrejas\Core\Desprovisionador ao excluir uma igreja - o
     * nome ja precisa vir com o prefixo da conta cPanel incluido (mesmo
     * nome usado em criarBancoDeDados/criarUsuarioBanco), pelo mesmo
     * motivo documentado em concederPrivilegios().
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
     * @return array{sucesso:bool, status_http:int, body:array}
     */
    public function excluirSubdominio(string $subdominioCompleto): array
    {
        return $this->chamar('SubDomain', 'delsubdomain', ['domain' => $subdominioCompleto]);
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

        // Resposta da UAPI: {"status": 1, "errors": null, ...} - status 1
        // e sucesso, 0 e falha (com "errors" preenchido).
        $sucesso = $status === 200 && (int) ($dados['status'] ?? 0) === 1;

        return [
            'sucesso' => $sucesso,
            'status_http' => $status,
            'body' => $dados,
        ];
    }
}
