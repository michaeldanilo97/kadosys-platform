<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cron - Geracao de parcelas de despesas recorrentes (KADOSYS Food)
|--------------------------------------------------------------------------
|
| Roda uma vez por dia (configurar no "Cron Jobs" do cPanel, ex.:
| php /home/kadosys1/apps/food/cron/gerar_despesas_recorrentes.php).
|
| Toda a logica de decisao (qual serie precisa de proxima parcela, se ja
| atingiu o limite de parcelas, etc) fica em
| ContaPagar::gerarProximasRecorrentes() - este arquivo e so o
| entrypoint de linha de comando que chama o Model e loga o resultado,
| mesmo padrao dos outros crons desta plataforma.
|
*/

require dirname(__DIR__) . '/vendor/autoload.php';

use Food\Models\ContaPagar;

function log_cron(string $mensagem): void
{
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n");
}

if (realpath($argv[0] ?? '') === __FILE__) {
    $geradas = ContaPagar::gerarProximasRecorrentes();

    log_cron("Concluido - {$geradas} parcela(s) recorrente(s) gerada(s).");
}
