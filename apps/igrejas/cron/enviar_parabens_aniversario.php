<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cron - E-mail automatico de parabens por aniversario (KADOSYS Igrejas)
|--------------------------------------------------------------------------
|
| Roda uma vez por dia, de manha (configurar no "Cron Jobs" do cPanel,
| ex.: php /home/kadosys1/apps/igrejas/cron/enviar_parabens_aniversario.php
| as 7h). Varre toda igreja provisionada automaticamente (tenant ativo
| no registro central) e, dentro do banco de cada uma, procura membros
| ativos que fazem aniversario hoje e tem e-mail cadastrado.
|
| A mensagem e a personalizada em Configuracoes > Aniversariantes de
| cada igreja (ou a padrao, se nenhuma foi definida), com os marcadores
| {nome} e {igreja} substituidos na hora do envio. A tabela
| aniversario_envios garante no maximo um e-mail por membro por ano,
| mesmo se este cron rodar mais de uma vez no mesmo dia.
|
*/

require dirname(__DIR__) . '/vendor/autoload.php';

use Igrejas\Core\Mailer;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Tenant;

function log_cron_aniversario(string $mensagem): void
{
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n");
}

function conectarTenant(Tenant $tenant): PDO
{
    $dbConfig = require dirname(__DIR__) . '/config/database.php';

    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=%s',
        $dbConfig['driver'],
        $dbConfig['host'],
        $dbConfig['port'],
        $tenant->dbName,
        $dbConfig['charset']
    );

    return new PDO($dsn, $tenant->dbUser, $tenant->dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function montarCorpoEmail(string $mensagem, string $nomeMembro, string $nomeIgreja): string
{
    $texto = str_replace(['{nome}', '{igreja}'], [$nomeMembro, $nomeIgreja], $mensagem);
    $textoHtml = nl2br(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'));

    return '<div style="font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #1f2937;">'
        . $textoHtml
        . '</div>';
}

function processarTenant(Tenant $tenant): void
{
    $pdo = conectarTenant($tenant);

    $aniversariantes = $pdo->query(
        "SELECT id, nome, email FROM membros
         WHERE status = 'ativo' AND data_nascimento IS NOT NULL
           AND MONTH(data_nascimento) = MONTH(CURDATE()) AND DAY(data_nascimento) = DAY(CURDATE())
           AND email IS NOT NULL AND email <> ''"
    )->fetchAll();

    if ($aniversariantes === []) {
        return;
    }

    $configLinha = $pdo->query('SELECT nome_igreja, mensagem_aniversario FROM configuracoes_igreja WHERE id = 1 LIMIT 1')
        ->fetch();

    $nomeIgreja = ($configLinha['nome_igreja'] ?? null) !== null ? (string) $configLinha['nome_igreja'] : $tenant->nomeIgreja;
    $mensagemBase = trim((string) ($configLinha['mensagem_aniversario'] ?? ''));
    $mensagemBase = $mensagemBase !== '' ? $mensagemBase : ConfiguracaoIgreja::MENSAGEM_ANIVERSARIO_PADRAO;

    $ano = (int) date('Y');
    $jaEnviadoStmt = $pdo->prepare('SELECT 1 FROM aniversario_envios WHERE membro_id = :membro_id AND ano = :ano LIMIT 1');
    $registrarStmt = $pdo->prepare('INSERT IGNORE INTO aniversario_envios (membro_id, ano, enviado_em) VALUES (:membro_id, :ano, NOW())');

    foreach ($aniversariantes as $membro) {
        $membroId = (int) $membro['id'];

        $jaEnviadoStmt->execute(['membro_id' => $membroId, 'ano' => $ano]);
        if ($jaEnviadoStmt->fetchColumn() !== false) {
            continue;
        }

        $corpo = montarCorpoEmail($mensagemBase, (string) $membro['nome'], $nomeIgreja);
        $enviado = Mailer::enviar(
            (string) $membro['email'],
            (string) $membro['nome'],
            'Feliz aniversário! 🎉',
            $corpo
        );

        if ($enviado) {
            $registrarStmt->execute(['membro_id' => $membroId, 'ano' => $ano]);
            log_cron_aniversario("Tenant {$tenant->slug}: e-mail de aniversario enviado para {$membro['nome']} ({$membro['email']}).");
        } else {
            log_cron_aniversario("Tenant {$tenant->slug}: falha ao enviar e-mail de aniversario para {$membro['email']}.");
        }
    }
}

// Guarda de execucao: so roda a rotina principal quando este arquivo e
// chamado diretamente (via cron/CLI), nunca quando e "require"-ado por
// um script de teste que so quer reusar as funcoes acima.
if (realpath($argv[0] ?? '') === __FILE__) {
    $tenants = Tenant::ativas();

    log_cron_aniversario(sprintf('Verificando aniversariantes em %d igreja(s) ativa(s).', count($tenants)));

    foreach ($tenants as $tenant) {
        try {
            processarTenant($tenant);
        } catch (\Throwable $exception) {
            log_cron_aniversario("Tenant {$tenant->slug}: erro inesperado - {$exception->getMessage()}");
        }
    }

    log_cron_aniversario('Concluido.');
}
