<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cron - Aplica downgrades agendados (KADOSYS Igrejas)
|--------------------------------------------------------------------------
|
| Roda uma vez por dia (configurar no "Cron Jobs" do cPanel, ex.:
| php /home/kadosys1/apps/igrejas/cron/aplicar_trocas_agendadas.php).
|
| IMPORTANTE: precisa rodar ANTES de cron/gerar_faturas_pix.php no
| mesmo dia (ex.: 5:00 pra este, 5:10 pra aquele) - senao um tenant Pix
| com downgrade agendado pro dia de hoje acabaria recebendo a proxima
| fatura ainda no valor do plano antigo (mais caro).
|
| Downgrade (plano mais barato) e agendado em vez de aplicado na hora
| pra nao tirar da igreja o que ela ja pagou no ciclo atual - ver
| Igrejas\Controllers\AssinaturaController::agendarDowngrade(). Este
| cron so aplica de fato quando o ciclo pago (proximo_vencimento) ja
| virou.
|
*/

require dirname(__DIR__) . '/vendor/autoload.php';

use Igrejas\Core\MercadoPagoClient;
use Igrejas\Models\AssinaturaTenant;
use Igrejas\Models\Plano;
use Igrejas\Models\Tenant;

function log_cron_trocas(string $mensagem): void
{
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n");
}

function atualizarPlanoNoBancoDoTenant(Tenant $tenant, string $plano): void
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

    $pdo = new PDO($dsn, $tenant->dbUser, $tenant->dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->prepare(
        'INSERT INTO configuracoes_igreja (id, plano) VALUES (1, :plano)
         ON DUPLICATE KEY UPDATE plano = VALUES(plano)'
    )->execute(['plano' => $plano]);
}

function aplicarTroca(Tenant $tenant, MercadoPagoClient $mp): void
{
    $novoPlano = $tenant->planoAgendado;

    if ($novoPlano === null) {
        return;
    }

    try {
        atualizarPlanoNoBancoDoTenant($tenant, $novoPlano);
    } catch (\Throwable $exception) {
        log_cron_trocas("Tenant {$tenant->slug}: falha ao atualizar o banco isolado - {$exception->getMessage()}");

        return;
    }

    Tenant::atualizarPlano($tenant->id, $novoPlano);
    Tenant::cancelarTrocaAgendada($tenant->id);

    if ($tenant->metodoPagamento === 'cartao' && isset(Plano::VALOR_MENSAL[$novoPlano])) {
        $assinatura = AssinaturaTenant::ativaDoTenant($tenant->id);

        if ($assinatura !== null) {
            try {
                $mp->atualizarAssinatura($assinatura->mpPreapprovalId, Plano::VALOR_MENSAL[$novoPlano]);
            } catch (\RuntimeException $exception) {
                log_cron_trocas("Tenant {$tenant->slug}: falha ao atualizar valor da assinatura no Mercado Pago - {$exception->getMessage()}");
            }
        }
    }

    log_cron_trocas("Tenant {$tenant->slug}: downgrade aplicado - agora no plano {$novoPlano}.");
}

if (realpath($argv[0] ?? '') === __FILE__) {
    $mp = new MercadoPagoClient();
    $tenants = Tenant::comTrocaAgendadaVencida();

    log_cron_trocas(sprintf('Verificando %d tenant(s) com troca de plano agendada vencida.', count($tenants)));

    foreach ($tenants as $tenant) {
        try {
            aplicarTroca($tenant, $mp);
        } catch (\Throwable $exception) {
            log_cron_trocas("Tenant {$tenant->slug}: erro inesperado - {$exception->getMessage()}");
        }
    }

    log_cron_trocas('Concluido.');
}
