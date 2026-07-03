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
            // Mysql::create_database/create_user exigem o nome ja com o
            // prefixo "usuario_" incluido nesta versao/configuracao do
            // cPanel (confirmado com um erro real em producao: "O nome
            // 't10' nao comeca com o prefixo obrigatorio 'kadosys1_'") -
            // diferente do comportamento classico de auto-prefixar a
            // partir so do sufixo.
            $this->executarOuFalhar($this->cpanel->criarBancoDeDados($dbNome), 'criar o banco de dados');
            $this->executarOuFalhar($this->cpanel->criarUsuarioBanco($dbNome, $dbSenha), 'criar o usuario do banco');
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
                $provisionamento->metodoPagamento,
                $subdominio,
                $dbNome,
                $dbNome,
                $dbSenha,
            );

            Tenant::marcarAtivo($tenantId);
            Provisionamento::vincularTenant($provisionamento->id, $tenantId);
            $this->importarBiblia($pdo, $provisionamento);
            $this->enviarEmailBoasVindas($provisionamento, $subdominio);
        } catch (\Throwable $exception) {
            Provisionamento::atualizarStatus($provisionamento->id, 'erro', $exception->getMessage());
        }
    }

    /**
     * Importa o texto biblico (NVI, ACF, AA) pro banco do tenant novo, a
     * partir dos dumps pre-gerados em database/seed/ - sem isso a
     * Projecao ate deixa escolher a referencia, mas o texto do
     * versiculo fica vazio ate alguem importar na mao, o que inviabiliza
     * o cadastro autoatendido. Falha aqui (de qualquer versao) nunca
     * reverte o provisionamento (banco/subdominio/admin ja prontos
     * nesse ponto) - so fica registrada como aviso.
     */
    private function importarBiblia(PDO $pdo, Provisionamento $provisionamento): void
    {
        foreach (['nvi', 'acf', 'aa'] as $versao) {
            try {
                $this->importarVersaoBiblia($pdo, $versao);
            } catch (\Throwable $exception) {
                Provisionamento::atualizarStatus(
                    $provisionamento->id,
                    'concluido',
                    "Aviso: falha ao importar o texto da Biblia ({$versao}) - " . $exception->getMessage()
                );
            }
        }
    }

    private function importarVersaoBiblia(PDO $pdo, string $versao): void
    {
        $caminho = dirname(__DIR__, 2) . "/database/seed/biblia_{$versao}.sql.gz";

        if (!is_file($caminho)) {
            return;
        }

        $comprimido = file_get_contents($caminho);

        if ($comprimido === false) {
            throw new \RuntimeException("Nao foi possivel ler {$caminho}.");
        }

        $sql = gzdecode($comprimido);

        if ($sql === false || $sql === '') {
            throw new \RuntimeException("Nao foi possivel descompactar {$caminho}.");
        }

        foreach ($this->dividirStatementsBiblia($sql) as $statement) {
            $pdo->exec($statement);
        }
    }

    /**
     * Diferente de dividirStatements() (usado no install.sql), aqui NAO
     * da pra dividir por ";" cru - o texto biblico tem ponto-e-virgula
     * (e parenteses) dentro das proprias strings, o que quebraria os
     * dados no meio. Cada bloco de INSERT no dump ja vem pre-lotado
     * (~500 versiculos por bloco, pra nao estourar o max_allowed_packet
     * do MySQL) e sempre termina com a mesma linha fixa - usa ela como
     * delimitador real do statement, em vez de qualquer ";" solto no
     * meio do texto.
     *
     * @return array<int, string>
     */
    private function dividirStatementsBiblia(string $sql): array
    {
        preg_match_all('/INSERT INTO biblia_versiculos.*?ON DUPLICATE KEY UPDATE texto = VALUES\(texto\);/s', $sql, $matches);

        return $matches[0];
    }

    /**
     * Falha no envio do e-mail nunca pode reverter o provisionamento (o
     * banco/subdominio ja foram criados com sucesso nesse ponto) - so
     * fica registrado como aviso, sem mudar o status de "concluido".
     */
    private function enviarEmailBoasVindas(Provisionamento $provisionamento, string $subdominio): void
    {
        try {
            $corpo = View::render('emails.boas-vindas', [
                'nomeAdmin' => $provisionamento->adminNome,
                'nomeIgreja' => $provisionamento->nomeIgreja,
                'urlAcesso' => 'https://' . $subdominio,
                'plano' => $provisionamento->plano,
            ]);

            $enviado = Mailer::enviar(
                $provisionamento->adminEmail,
                $provisionamento->adminNome,
                'Bem-vindo ao KADOSYS Igrejas - sua conta esta pronta!',
                $corpo,
            );

            if (!$enviado) {
                Provisionamento::atualizarStatus($provisionamento->id, 'concluido', 'Aviso: nao foi possivel enviar o e-mail de boas-vindas.');
            }
        } catch (\Throwable $exception) {
            Provisionamento::atualizarStatus($provisionamento->id, 'concluido', 'Aviso: falha ao enviar o e-mail de boas-vindas - ' . $exception->getMessage());
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
