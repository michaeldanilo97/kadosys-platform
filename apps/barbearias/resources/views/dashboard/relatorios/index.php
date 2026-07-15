<?php

use Barbearias\Models\Profissional;

/**
 * @var array $config
 * @var array{receitas: float, despesas: float, saldo: float} $resumoFinanceiro
 * @var array{agendado: int, concluido: int, cancelado: int} $agendamentosPorStatus
 * @var float $ticketMedio
 * @var int $atendimentosConcluidos
 * @var array<int, array{profissional: Profissional, horasDisponiveis: float, horasOcupadas: float, taxaOcupacao: float}> $ocupacao
 * @var string $dataInicio
 * @var string $dataFim
 */
$basePath = $config['base_path'] ?? '';
$moeda = static fn (float $valor): string => 'R$ ' . number_format($valor, 2, ',', '.');
$totalAgendamentos = $agendamentosPorStatus['agendado'] + $agendamentosPorStatus['concluido'] + $agendamentosPorStatus['cancelado'];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Gestão</p>
            <h1 class="dashboard-title">Relatórios</h1>
            <p class="dash-page-subtitle">Visão consolidada do período - faturamento, agendamentos e ocupação da equipe.</p>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <form method="GET" action="<?= $basePath ?>/dashboard/relatorios" class="crud-form-grid">
            <div class="form-field">
                <label for="data_inicio">De</label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-field">
                <label for="data_fim">Até</label>
                <input type="date" id="data_fim" name="data_fim" value="<?= htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="crud-form-actions" style="align-self:end;">
                <button type="submit" class="btn-k btn-k-grad">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="kpi-grid">
        <div class="glass-card kpi-card">
            <p class="kpi-label">Receitas do período</p>
            <p class="kpi-valor"><?= $moeda($resumoFinanceiro['receitas']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Despesas do período</p>
            <p class="kpi-valor"><?= $moeda($resumoFinanceiro['despesas']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Saldo</p>
            <p class="kpi-valor"><?= $moeda($resumoFinanceiro['saldo']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Ticket médio</p>
            <p class="kpi-valor"><?= $moeda($ticketMedio) ?></p>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Agendamentos do período</h2>
        </div>
        <div class="kpi-grid">
            <div class="glass-card kpi-card">
                <p class="kpi-label">Total</p>
                <p class="kpi-valor"><?= $totalAgendamentos ?></p>
            </div>
            <div class="glass-card kpi-card">
                <p class="kpi-label">Concluídos</p>
                <p class="kpi-valor"><?= $agendamentosPorStatus['concluido'] ?></p>
            </div>
            <div class="glass-card kpi-card">
                <p class="kpi-label">Agendados</p>
                <p class="kpi-valor"><?= $agendamentosPorStatus['agendado'] ?></p>
            </div>
            <div class="glass-card kpi-card">
                <p class="kpi-label">Cancelados</p>
                <p class="kpi-valor"><?= $agendamentosPorStatus['cancelado'] ?></p>
            </div>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Ocupação por profissional</h2>
        </div>
        <p class="dash-page-subtitle" style="margin: 0 0 1rem;">Estimativa com base no expediente cadastrado (dias/horário de atendimento) - não desconta férias/folgas pontuais.</p>

        <?php if ($ocupacao === []): ?>
            <p class="crud-empty">Nenhum profissional ativo cadastrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Profissional</th>
                            <th>Horas disponíveis</th>
                            <th>Horas ocupadas</th>
                            <th>Taxa de ocupação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ocupacao as $linha): ?>
                            <tr>
                                <td><?= htmlspecialchars($linha['profissional']->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= number_format($linha['horasDisponiveis'], 1, ',', '.') ?>h</td>
                                <td class="text-dim"><?= number_format($linha['horasOcupadas'], 1, ',', '.') ?>h</td>
                                <td>
                                    <span class="status-badge <?= $linha['taxaOcupacao'] >= 70 ? 'ok' : ($linha['taxaOcupacao'] >= 40 ? 'dim' : 'danger') ?>">
                                        <?= number_format($linha['taxaOcupacao'], 1, ',', '.') ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
