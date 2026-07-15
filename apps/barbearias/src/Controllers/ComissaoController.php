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
 * Fechamento de comissao por profissional: soma o valor dos
 * atendimentos CONCLUIDOS num periodo e aplica o percentual de
 * comissao cadastrado em cada profissional (ver
 * Barbearias\Models\Profissional::$percentualComissao).
 */
final class ComissaoController extends Controller
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

        $profissionalId = (int) $this->request->input('profissional_id', 0);

        $inicioSql = $dataInicio . ' 00:00:00';
        $fimSql = $dataFim . ' 23:59:59';

        $fechamento = Agendamento::comissoesPorProfissional($barbeariaId, $inicioSql, $fimSql, $profissionalId);

        $totalGeral = array_sum(array_column($fechamento, 'totalServicos'));
        $totalComissoes = array_sum(array_column($fechamento, 'totalComissao'));

        $detalhe = null;

        if ($profissionalId > 0) {
            $profissional = Profissional::find($profissionalId, $barbeariaId);

            if ($profissional !== null) {
                $atendimentos = Agendamento::concluidosPorProfissionalNoPeriodo($barbeariaId, $profissionalId, $inicioSql, $fimSql);
                $valoresPagos = FinanceiroLancamento::mapaPorAgendamentos(array_map(static fn (Agendamento $a) => $a->id, $atendimentos));

                $detalhe = [
                    'profissional' => $profissional,
                    'atendimentos' => $atendimentos,
                    'valoresPagos' => $valoresPagos,
                ];
            }
        }

        echo $this->view('dashboard.comissoes.index', [
            'pageTitle' => 'Comissões - KADOSYS Barbearias',
            'activeMenu' => 'comissoes',
            'user' => $this->usuario(),
            'barbearia' => Barbearia::find($barbeariaId),
            'profissionais' => Profissional::ativos($barbeariaId),
            'fechamento' => $fechamento,
            'totalGeral' => $totalGeral,
            'totalComissoes' => $totalComissoes,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'profissionalId' => $profissionalId,
            'detalhe' => $detalhe,
        ], 'dashboard');
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
