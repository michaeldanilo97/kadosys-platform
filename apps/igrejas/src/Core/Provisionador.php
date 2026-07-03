<?php

declare(strict_types=1);

namespace Igrejas\Core;

use Igrejas\Models\Provisionamento;
use Igrejas\Models\Tenant;
use PDO;

/**
 * Provisionamento automatico de uma nova igreja: cria o banco de dados
 * e o subdominio via API do cPanel, instala o schema (mesmo
 * database/install.sql usado em qualquer instalacao nova), e cria a
 * primeira igreja (configuracoes_igreja) + o usuario administrador
 * dentro desse banco novo.
 *
 * Disparado pelo webhook do Mercado Pago quando o pagamento de um
 * cadastro publico (ver CadastroController) e confirmado. Cada etapa
 * que falha interrompe o processo e marca o provisionamento como
 * "erro" com a mensagem exata, pra dar pra diagnosticar sem deixar a
 * igreja pela metade sem nenhum rastro.
 */
final class Provisionador
{
    public function __construct(
        private readonly CpanelUapiClient $cpanel,
    ) {
    }

    public function provisionar(Provisionamento $provisionamento): void
    {
        $cpanelConfig = require dirname(__DIR__, 2) . '/config/cpanel.php';

        if (!$this->cpanel->configurado()) {
            Provisionamento::atualizarStatus($provisionamento->id, 'erro', 'Credenciais do cPanel nao configuradas no servidor.');

            return;
        }

        if ($cpanelConfig['root_domain'] === '' || $cpanelConfig['subdomain_docroot'] === '') {
            Provisionamento::atualizarStatus($provisionamento->id, 'erro', 'CPANEL_ROOT_DOMAIN ou CPANEL_SUBDOMAIN_DOCROOT nao configurados no servidor.');

            return;
        }

        Provisionamento::atualizarStatus($provisionamento->id, 'provisionando');

        // Sufixo curto (nao o nome da igreja) para o nome do banco/
        // usuario MySQL - cPanel prefixa tudo com "usuario_", e nomes de
        // usuario MySQL tem limite de caracteres bem curto (16-32
        // dependendo da versao); um slug de igreja inteiro facilmente
        // estouraria esse limite.
        $sufixo = 't' . $provisionamento->id;
        $dbNome = $cpanelConfig['username'] . '_' . $sufixo;
        $dbSenha = bin2hex(random_bytes(16));
        $subdominio = $provisionamento->slug . '.' . $cpanelConfig['root_domain'];

        try {
            $this->executarOuFalhar($this->cpanel->criarBancoDeDados($sufixo), 'criar o banco de dados');
            $this->executarOuFalhar($this->cpanel->criarUsuarioBanco($sufixo, $dbSenha), 'criar o usuario do banco');
            $this->executarOuFalhar($this->cpanel->concederPrivilegios($dbNome, $dbNome), 'conceder privilegios ao usuario do banco');
            $this->executarOuFalhar(
                $this->cpanel->criarSubdominio($provisionamento->slug, $cpanelConfig['root_domain'], $cpanelConfig['subdomain_docroot']),
                'criar o subdominio'
            );

            $pdo = $this->conectar($dbNome, $dbNome, $dbSenha);
            $this->instalarEsquema($pdo);
            $this->inserirDadosIniciais($pdo, $provisionamento);

            $tenantId = Tenant::criar(
                $provisionamento->slug,
                $provisionamento->nomeIgreja,
                $provisionamento->plano,
                $subdominio,
                $dbNome,
                $dbNome,
                $dbSenha,
            );

            Tenant::marcarAtivo($tenantId);
            Provisionamento::vincularTenant($provisionamento->id, $tenantId);
        } catch (\Throwable $exception) {
            Provisionamento::atualizarStatus($provisionamento->id, 'erro', $exception->getMessage());
        }
    }

    /**
     * @param array{sucesso:bool, status_http:int, body:array} $resposta
     */
    private function executarOuFalhar(array $resposta, string $etapa): void
    {
        if (!$resposta['sucesso']) {
            $mensagem = implode('; ', array_map('strval', $resposta['body']['errors'] ?? ['resposta inesperada do cPanel']));

            throw new \RuntimeException("Falha ao {$etapa}: {$mensagem}");
        }
    }

    private function conectar(string $dbNome, string $dbUsuario, string $dbSenha): PDO
    {
        $dbConfig = require dirname(__DIR__, 2) . '/config/database.php';

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $dbConfig['driver'],
            $dbConfig['host'],
            $dbConfig['port'],
            $dbNome,
            $dbConfig['charset']
        );

        return new PDO($dsn, $dbUsuario, $dbSenha, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function instalarEsquema(PDO $pdo): void
    {
        $caminho = dirname(__DIR__, 2) . '/database/install.sql';
        $sql = file_get_contents($caminho);

        if ($sql === false) {
            throw new \RuntimeException('Nao foi possivel ler database/install.sql.');
        }

        foreach ($this->dividirStatements($sql) as $statement) {
            $pdo->exec($statement);
        }
    }

    private function inserirDadosIniciais(PDO $pdo, Provisionamento $provisionamento): void
    {
        $pdo->prepare(
            'INSERT INTO configuracoes_igreja (id, nome_igreja, plano)
             VALUES (1, :nome_igreja, :plano)
             ON DUPLICATE KEY UPDATE nome_igreja = VALUES(nome_igreja), plano = VALUES(plano)'
        )->execute([
            'nome_igreja' => $provisionamento->nomeIgreja,
            'plano' => $provisionamento->plano,
        ]);

        $pdo->prepare(
            'INSERT INTO users (name, email, password, role, active)
             VALUES (:name, :email, :password, "admin", 1)'
        )->execute([
            'name' => $provisionamento->adminNome,
            'email' => $provisionamento->adminEmail,
            'password' => $provisionamento->adminSenhaHash,
        ]);
    }

    /**
     * Divide o conteudo de install.sql em statements individuais
     * (remove linhas de comentario "--" e separa por ";"). Nao ha
     * procedures/triggers com DELIMITER nesse arquivo, entao um split
     * simples e suficiente e mais facil de auditar que um parser SQL
     * completo.
     *
     * @return array<int, string>
     */
    private function dividirStatements(string $sql): array
    {
        $linhas = array_filter(
            explode("\n", $sql),
            static fn (string $linha): bool => !str_starts_with(trim($linha), '--')
        );

        $semComentarios = implode("\n", $linhas);
        $statements = array_filter(array_map('trim', explode(';', $semComentarios)));

        return array_values($statements);
    }
}
