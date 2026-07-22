<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cron - Suspende academias com assinatura cancelada (KADOSYS Academias)
|--------------------------------------------------------------------------
|
| Roda uma vez por dia (configurar no "Cron Jobs" do cPanel, ex.:
| php /home/kadosys1/apps/academias/cron/suspender_assinaturas_canceladas.php).
|
| Complementa o cancelamento self-service (ver
| Academias\Controllers\ConfiguracaoController::cancelarAssinatura):
| cancelar so marca "nao vai renovar", o acesso continua liberado ate
| proximo_vencimento (ja pago). Este cron e quem de fato bloqueia o
| acesso quando esse prazo passa, pra quem pagava por cartao (Pix ja e
| coberto de outro jeito: cron/gerar_faturas_pix.php simplesmente para
| de gerar fatura nova, e o proprio AuthMiddleware bloqueia quando a
| ultima fatura vence).
|
*/

require dirname(__DIR__) . '/vendor/autoload.php';

use Academias\Models\Academia;

function log_cron_suspensao(string $mensagem): void
{
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n");
}

// Guarda de execucao: so roda a rotina principal quando este arquivo e
// chamado diretamente (via cron/CLI), nunca quando e "require"-ado por
// um script de teste que so quer reusar as funcoes acima.
if (realpath($argv[0] ?? '') === __FILE__) {
    $academias = Academia::canceladasComCicloEncerrado();

    log_cron_suspensao(sprintf('%d academia(s) com assinatura cancelada e ciclo pago encerrado.', count($academias)));

    foreach ($academias as $academia) {
        Academia::marcarSuspensa($academia->id);
        log_cron_suspensao("Academia {$academia->slug}: acesso suspenso (assinatura cancelada em {$academia->canceladoEm}).");
    }

    log_cron_suspensao('Concluído.');
}
