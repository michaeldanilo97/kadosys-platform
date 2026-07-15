<?php

declare(strict_types=1);

namespace Barbearias\Controllers;

use Barbearias\Core\Auth;
use Barbearias\Core\Controller;
use Barbearias\Models\Agendamento;
use Barbearias\Models\Barbearia;
use Barbearias\Models\FinanceiroLancamento;
use Barbearias\Models\Profissional;
use Barbearias\Models\User;

/**
 * Relatorio consolidado: faturamento, agendamentos por status, ticket
 * medio e taxa de ocupacao por profissional, tudo no mesmo periodo.
 */
final class RelatorioController extends Controller
{
    public function index(): void
    {
        $barbeariaId = $this->barbeariaId();

        $hoje = new \DateTimeImmutable('today');
        $dataInicio = trim((string) $this->request->input('data_inicio', ''));
        $dataFim = trim((string) $this->request->input('data_fim', ''));

        if ($dataInicio === '' || !$this->dataValida($dataInicio)) {
            $dataInicio = $hoje->modify('first day of this month')->format('Y-m-d');
        }

        if ($dataFim === '' || !$this->dataValida($dataFim)) {
            $dataFim = $hoje->modify('last day of this month')->format('Y-m-d');
        }

        $inicioData = new \DateTimeImmutable($dataInicio);
        $fimData = new \DateTimeImmutable($dataFim);
        $inicioSql = $dataInicio . ' 00:00:00';
        $fimSql = $dataFim . ' 23:59:59';

        $resumoFinanceiro = FinanceiroLancamento::resumoDoPeriodo($barbeariaId, $dataInicio, $dataFim);
        $agendamentosPorStatus = Agendamento::contarPorStatusNoPeriodo($barbeariaId, $inicioSql, $fimSql);
        $faturamentoServicos = Agendamento::faturamentoServicosNoPeriodo($barbeariaId, $inicioSql, $fimSql);
        $ticketMedio = $faturamentoServicos['quantidade'] > 0
            ? $faturamentoServicos['total'] / $faturamentoServicos['quantidade']
            : 0.0;

        $minutosOcupados = Agendamento::minutosOcupadosPorProfissional($barbeariaId, $inicioSql, $fimSql);
        $ocupacao = [];

        foreach (Profissional::ativos($barbeariaId) as $profissional) {
            $horasDisponiveis = $this->horasDisponiveisNoPeriodo($profissional, $inicioData, $fimData);
            $horasOcupadas = ($minutosOcupados[$profissional->id] ?? 0) / 60;

            $ocupacao[] = [
                'profissional' => $profissional,
                'horasDisponiveis' => $horasDisponiveis,
                'horasOcupadas' => $horasOcupadas,
                'taxaOcupacao' => $horasDisponiveis > 0 ? min(100.0, ($horasOcupadas / $horasDisponiveis) * 100) : 0.0,
            ];
        }

        echo $this->view('dashboard.relatorios.index', [
            'pageTitle' => 'Relatórios - KADOSYS Barbearias',
            'activeMenu' => 'relatorios',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'resumoFinanceiro' => $resumoFinanceiro,
            'agendamentosPorStatus' => $agendamentosPorStatus,
            'ticketMedio' => $ticketMedio,
            'atendimentosConcluidos' => $faturamentoServicos['quantidade'],
            'ocupacao' => $ocupacao,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
        ], 'dashboard');
    }

    /**
     * Aproximacao das horas de expediente do profissional dentro do
     * periodo, com base nos dias da semana que ele atende
     * (dias_atendimento) e no horario cadastrado - nao desconta
     * bloqueios de agenda pontuais (ferias/folgas), entao e uma
     * estimativa de capacidade, nao um calculo exato de disponibilidade
     * real.
     */
    private function horasDisponiveisNoPeriodo(Profissional $profissional, \DateTimeImmutable $inicio, \DateTimeImmutable $fim): float
    {
        if ($profissional->horarioInicio === null || $profissional->horarioFim === null || $profissional->diasAtendimento === []) {
            return 0.0;
        }

        $minutosPorDia = ((int) strtotime($profissional->horarioFim) - (int) strtotime($profissional->horarioInicio)) / 60;

        if ($minutosPorDia <= 0) {
            return 0.0;
        }

        $dias = 0;
        $cursor = $inicio;

        while ($cursor <= $fim) {
            if (in_array((int) $cursor->format('w'), $profissional->diasAtendimento, true)) {
                $dias++;
            }

            $cursor = $cursor->modify('+1 day');
        }

        return ($dias * $minutosPorDia) / 60;
    }

    private function dataValida(string $data): bool
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $data);

        return $dt !== false && $dt->format('Y-m-d') === $data;
    }

    private function usuario(): ?User
    {
        return (new Auth($this->config))->user();
    }

    private function barbeariaId(): int
    {
        return $this->usuario()?->barbeariaId ?? 0;
    }
}
