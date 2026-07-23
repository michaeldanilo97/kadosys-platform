<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cron - Suspende restaurantes com assinatura cancelada (KADOSYS Food)
|--------------------------------------------------------------------------
|
| Roda uma vez por dia (configurar no "Cron Jobs" do cPanel, ex.:
| php /home/kadosys1/apps/food/cron/suspender_assinaturas_canceladas.php).
|
| Complementa o cancelamento self-service (ver
| Food\Controllers\ConfiguracaoController::cancelarAssinatura):
| cancelar so marca "nao vai renovar", o acesso continua liberado ate
| proximo_vencimento (ja pago). Este cron e quem de fato bloqueia o
| acesso quando esse prazo passa, pra quem pagava por cartao (Pix ja e
| coberto de outro jeito: cron/gerar_faturas_pix.php simplesmente para
| de gerar fatura nova, e o proprio AuthMiddleware bloqueia quando a
| ultima fatura vence).
|
*/

require dirname(__DIR__) . '/vendor/autoload.php';

use Food\Models\Restaurante;

function log_cron_suspensao(string $mensagem): void
{
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n");
}

// Guarda de execucao: so roda a rotina principal quando este arquivo e
// chamado diretamente (via cron/CLI), nunca quando e "require"-ado por
// um script de teste que so quer reusar as funcoes acima.
if (realpath($argv[0] ?? '') === __FILE__) {
    $restaurantes = Restaurante::canceladosComCicloEncerrado();

    log_cron_suspensao(sprintf('%d restaurante(s) com assinatura cancelada e ciclo pago encerrado.', count($restaurantes)));

    foreach ($restaurantes as $restaurante) {
        Restaurante::marcarSuspensa($restaurante->id);
        log_cron_suspensao("Restaurante {$restaurante->slug}: acesso suspenso (assinatura cancelada em {$restaurante->canceladoEm}).");
    }

    log_cron_suspensao('Concluído.');
}
