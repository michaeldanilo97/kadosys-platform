<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cron - Lembrete automatico de agendamento por e-mail (KADOSYS Barbearias)
|--------------------------------------------------------------------------
|
| Roda uma vez por dia, de tarde/noite (configurar no "Cron Jobs" do
| cPanel, ex.: php /home/kadosys1/apps/barbearias/cron/enviar_lembretes_agendamento.php
| as 18h). Beneficio dos planos Plus/Premium (ver Barbearias\Models\Plano::
| FEATURES) - barbearia no Essencial nao recebe lembrete automatico.
|
| Avisa o cliente sobre o agendamento de AMANHA, so quando ha e-mail
| cadastrado (o sistema nao tem canal de WhatsApp configurado). A coluna
| agendamentos.lembrete_enviado_em garante no maximo um envio por
| agendamento, mesmo se este cron rodar mais de uma vez no mesmo dia.
|
*/

require dirname(__DIR__) . '/vendor/autoload.php';

use Barbearias\Core\Mailer;
use Barbearias\Core\View;
use Barbearias\Models\Agendamento;

View::setBasePath(dirname(__DIR__) . '/resources/views');

function log_cron_lembrete(string $mensagem): void
{
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n");
}

function enviarLembrete(array $agendamento): bool
{
    $dataHora = new \DateTimeImmutable($agendamento['dataHora']);

    $corpo = View::render('emails.lembrete-agendamento', [
        'clienteNome' => $agendamento['clienteNome'],
        'barbeariaNome' => $agendamento['barbeariaNome'],
        'profissionalNome' => $agendamento['profissionalNome'],
        'servicoNome' => $agendamento['servicoNome'],
        'dataFormatada' => $dataHora->format('d/m/Y'),
        'horaFormatada' => $dataHora->format('H:i'),
    ]);

    return Mailer::enviar(
        $agendamento['clienteEmail'],
        $agendamento['clienteNome'],
        'Lembrete: seu horário amanhã na ' . $agendamento['barbeariaNome'],
        $corpo
    );
}

// Guarda de execucao: so roda a rotina principal quando este arquivo e
// chamado diretamente (via cron/CLI), nunca quando e "require"-ado por
// um script de teste que so quer reusar as funcoes acima.
if (realpath($argv[0] ?? '') === __FILE__) {
    $agendamentos = Agendamento::pendentesLembreteAmanha();

    log_cron_lembrete(sprintf('Verificando %d agendamento(s) de amanhã sem lembrete enviado.', count($agendamentos)));

    foreach ($agendamentos as $agendamento) {
        try {
            $enviado = enviarLembrete($agendamento);

            if ($enviado) {
                Agendamento::marcarLembreteEnviado($agendamento['id']);
                log_cron_lembrete("Agendamento #{$agendamento['id']}: lembrete enviado para {$agendamento['clienteEmail']}.");
            } else {
                log_cron_lembrete("Agendamento #{$agendamento['id']}: falha ao enviar lembrete para {$agendamento['clienteEmail']}.");
            }
        } catch (\Throwable $exception) {
            log_cron_lembrete("Agendamento #{$agendamento['id']}: erro inesperado - {$exception->getMessage()}");
        }
    }

    log_cron_lembrete('Concluído.');
}
