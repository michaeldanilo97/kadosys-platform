<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cron - Lembrete de ultima hora por e-mail (KADOSYS Barbearias)
|--------------------------------------------------------------------------
|
| Complementa cron/enviar_lembretes_agendamento.php (aviso do dia
| anterior): este aqui roda A CADA 20 MINUTOS (configurar no "Cron
| Jobs" do cPanel com o intervalo "a cada 20 minutos", chamando
| php /home/kadosys1/apps/barbearias/cron/enviar_lembretes_proximos.php)
| e avisa o cliente quando o proprio horario esta chegando - qualquer
| agendamento comecando dentro dos proximos ~25min (a janela um pouco
| maior que os 20min do intervalo do cron cobre um eventual atraso na
| execucao, sem deixar nenhum agendamento passar sem aviso). Mesmo
| beneficio dos planos Plus/Premium, mesma coluna de e-mail - so muda a
| coluna de controle de envio (lembrete_proximo_enviado_em), separada
| da do aviso do dia anterior, ja que os dois lembretes sao independentes.
|
*/

require dirname(__DIR__) . '/vendor/autoload.php';

use Barbearias\Core\Mailer;
use Barbearias\Core\View;
use Barbearias\Models\Agendamento;

View::setBasePath(dirname(__DIR__) . '/resources/views');

function log_cron_lembrete_proximo(string $mensagem): void
{
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n");
}

function enviarLembreteProximo(array $agendamento): bool
{
    $dataHora = new \DateTimeImmutable($agendamento['dataHora']);

    $corpo = View::render('emails.lembrete-agendamento-proximo', [
        'clienteNome' => $agendamento['clienteNome'],
        'barbeariaNome' => $agendamento['barbeariaNome'],
        'profissionalNome' => $agendamento['profissionalNome'],
        'servicoNome' => $agendamento['servicoNome'],
        'horaFormatada' => $dataHora->format('H:i'),
    ]);

    return Mailer::enviar(
        $agendamento['clienteEmail'],
        $agendamento['clienteNome'],
        'Seu horário está chegando - ' . $agendamento['barbeariaNome'],
        $corpo
    );
}

// Guarda de execucao: so roda a rotina principal quando este arquivo e
// chamado diretamente (via cron/CLI), nunca quando e "require"-ado por
// um script de teste que so quer reusar as funcoes acima.
if (realpath($argv[0] ?? '') === __FILE__) {
    $agendamentos = Agendamento::pendentesLembreteProximo();

    log_cron_lembrete_proximo(sprintf('Verificando %d agendamento(s) comecando nos proximos ~25min.', count($agendamentos)));

    foreach ($agendamentos as $agendamento) {
        try {
            $enviado = enviarLembreteProximo($agendamento);

            if ($enviado) {
                Agendamento::marcarLembreteProximoEnviado($agendamento['id']);
                log_cron_lembrete_proximo("Agendamento #{$agendamento['id']}: lembrete de ultima hora enviado para {$agendamento['clienteEmail']}.");
            } else {
                log_cron_lembrete_proximo("Agendamento #{$agendamento['id']}: falha ao enviar lembrete de ultima hora para {$agendamento['clienteEmail']}.");
            }
        } catch (\Throwable $exception) {
            log_cron_lembrete_proximo("Agendamento #{$agendamento['id']}: erro inesperado - {$exception->getMessage()}");
        }
    }

    log_cron_lembrete_proximo('Concluído.');
}
