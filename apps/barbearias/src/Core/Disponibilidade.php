<?php

declare(strict_types=1);

namespace Barbearias\Core;

use Barbearias\Models\Agendamento;
use Barbearias\Models\BloqueioAgenda;
use Barbearias\Models\Profissional;
use Barbearias\Models\Servico;

/**
 * Calculo dos horarios livres de um profissional num dia, pro servico
 * escolhido - compartilhado entre o agendamento publico
 * (Barbearias\Controllers\AgendamentoPublicoController) e o
 * reagendamento pela area do cliente
 * (Barbearias\Controllers\ClienteAreaController), pra nao duplicar a
 * mesma logica de sobreposicao (agendamentos + bloqueios de agenda) em
 * dois lugares.
 */
final class Disponibilidade
{
    /** Intervalo entre os horarios sugeridos, em minutos. */
    private const GRANULARIDADE_MINUTOS = 15;

    /**
     * $excluirAgendamentoId serve pro reagendamento: o proprio
     * agendamento que esta sendo movido ainda esta 'agendado' ate a
     * troca ser confirmada, entao sem isso ele apareceria como
     * conflito consigo mesmo.
     *
     * @return array<int, string>
     */
    public static function horariosLivres(
        int $barbeariaId,
        Profissional $profissional,
        Servico $servico,
        string $data,
        int $excluirAgendamentoId = 0,
    ): array {
        if ($profissional->horarioInicio === null || $profissional->horarioFim === null) {
            return [];
        }

        try {
            $dia = new \DateTimeImmutable($data);
        } catch (\Exception) {
            return [];
        }

        $hoje = new \DateTimeImmutable('today');

        if ($dia < $hoje) {
            return [];
        }

        $diaSemana = (int) $dia->format('w');

        if (!in_array($diaSemana, $profissional->diasAtendimento, true)) {
            return [];
        }

        $inicioExpediente = new \DateTimeImmutable($data . ' ' . $profissional->horarioInicio);
        $fimExpediente = new \DateTimeImmutable($data . ' ' . $profissional->horarioFim);
        $duracao = $servico->duracaoMinutos;
        $agora = new \DateTimeImmutable();

        $ocupados = Agendamento::doDiaPorProfissional($barbeariaId, $profissional->id, $data, $excluirAgendamentoId);
        $bloqueios = BloqueioAgenda::doProfissionalNoPeriodo(
            $profissional->id,
            $dia->format('Y-m-d 00:00:00'),
            $dia->modify('+1 day')->format('Y-m-d 00:00:00'),
        );

        $slots = [];
        $cursor = $inicioExpediente;

        while ($cursor->modify('+' . $duracao . ' minutes') <= $fimExpediente) {
            $fimSlot = $cursor->modify('+' . $duracao . ' minutes');

            if ($cursor >= $agora) {
                $livre = true;

                foreach ($ocupados as $ocupado) {
                    $ocupadoInicio = new \DateTimeImmutable($ocupado->dataHora);
                    $ocupadoFim = $ocupadoInicio->modify('+' . $ocupado->servicoDuracao . ' minutes');

                    if ($cursor < $ocupadoFim && $fimSlot > $ocupadoInicio) {
                        $livre = false;

                        break;
                    }
                }

                foreach ($bloqueios as $bloqueio) {
                    $bloqueioInicio = new \DateTimeImmutable($bloqueio->dataInicio);
                    $bloqueioFim = new \DateTimeImmutable($bloqueio->dataFim);

                    if ($cursor < $bloqueioFim && $fimSlot > $bloqueioInicio) {
                        $livre = false;

                        break;
                    }
                }

                if ($livre) {
                    $slots[] = $cursor->format('H:i');
                }
            }

            $cursor = $cursor->modify('+' . self::GRANULARIDADE_MINUTOS . ' minutes');
        }

        return $slots;
    }
}
