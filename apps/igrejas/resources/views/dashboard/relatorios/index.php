<?php
/**
 * @var array $config
 * @var string $mes
 * @var int $membrosAtivos
 * @var int $novosMembrosNoMes
 * @var int $ministeriosAtivos
 * @var int $gruposAtivos
 * @var array<int, \Igrejas\Models\Culto> $cultosDoMes
 * @var float $mediaPresencas
 * @var array{entradas: float, saidas: float, saldo: float} $financeiroTotais
 * @var array<int, array{categoria: string, tipo: string, total: float}> $financeiroPorCategoria
 * @var float $patrimonioValorTotal
 * @var int $patrimonioAtivos
 */
$basePath = $config['base_path'] ?? '';
$mesFormatado = (new DateTimeImmutable($mes . '-01'))->format('m/Y');
$statusLabels = ['agendado' => 'Agendado', 'realizado' => 'Realizado', 'cancelado' => 'Cancelado'];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Relatórios</h1>
        <p class="dash-page-subtitle">Indicadores consolidados da igreja - referência <?= $mesFormatado ?>.</p>
    </div>
</div>

<form method="GET" action="<?= $basePath ?>/dashboard/relatorios" class="crud-filters">
    <input type="month" name="mes" value="<?= htmlspecialchars($mes, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn-k btn-k-ghost"><i class="bi bi-funnel"></i> Ver mês</button>
</form>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon blue"><i class="bi bi-people"></i></div>
        </div>
        <div class="value"><?= $membrosAtivos ?></div>
        <div class="label">Membros ativos</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon violet"><i class="bi bi-person-plus"></i></div>
        </div>
        <div class="value"><?= $novosMembrosNoMes ?></div>
        <div class="label">Novos membros no mês</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon cyan"><i class="bi bi-calendar2-week"></i></div>
        </div>
        <div class="value"><?= count($cultosDoMes) ?></div>
        <div class="label">Cultos no mês</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon cyan"><i class="bi bi-graph-up"></i></div>
        </div>
        <div class="value"><?= number_format($mediaPresencas, 1, ',', '.') ?></div>
        <div class="label">Média de presenças (realizados)</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon violet"><i class="bi bi-diagram-3"></i></div>
        </div>
        <div class="value"><?= $ministeriosAtivos ?></div>
        <div class="label">Ministérios ativos</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon violet"><i class="bi bi-people-fill"></i></div>
        </div>
        <div class="value"><?= $gruposAtivos ?></div>
        <div class="label">Grupos ativos</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon green"><i class="bi bi-arrow-down-circle"></i></div>
        </div>
        <div class="value">R$ <?= number_format($financeiroTotais['entradas'], 2, ',', '.') ?></div>
        <div class="label">Entradas do mês</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon red"><i class="bi bi-arrow-up-circle"></i></div>
        </div>
        <div class="value">R$ <?= number_format($financeiroTotais['saidas'], 2, ',', '.') ?></div>
        <div class="label">Saídas do mês</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon <?= $financeiroTotais['saldo'] >= 0 ? 'blue' : 'red' ?>"><i class="bi bi-wallet2"></i></div>
        </div>
        <div class="value">R$ <?= number_format($financeiroTotais['saldo'], 2, ',', '.') ?></div>
        <div class="label">Saldo do mês</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon blue"><i class="bi bi-building"></i></div>
        </div>
        <div class="value">R$ <?= number_format($patrimonioValorTotal, 2, ',', '.') ?></div>
        <div class="label"><?= $patrimonioAtivos ?> bem<?= $patrimonioAtivos === 1 ? '' : 's' ?> ativo<?= $patrimonioAtivos === 1 ? '' : 's' ?> (valor total)</div>
    </div>
</div>

<div class="dash-panels-row">
    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-cash-coin"></i> Financeiro por categoria</h2>
        </div>
        <?php if ($financeiroPorCategoria === []): ?>
            <p class="crud-text-dim">Nenhum lançamento financeiro neste mês.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Tipo</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($financeiroPorCategoria as $linha): ?>
                            <tr>
                                <td><?= htmlspecialchars($linha['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="status-badge <?= $linha['tipo'] === 'entrada' ? 'is-entrada' : 'is-saida' ?>">
                                        <?= $linha['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?>
                                    </span>
                                </td>
                                <td>R$ <?= number_format($linha['total'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-calendar2-week"></i> Cultos do mês</h2>
        </div>
        <?php if ($cultosDoMes === []): ?>
            <p class="crud-text-dim">Nenhum culto cadastrado neste mês.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Culto</th>
                            <th>Presentes</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cultosDoMes as $culto): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?>
                                    <div class="crud-text-dim" style="font-size: 0.78rem;"><?= htmlspecialchars($culto->dataHoraFormatada(), ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td><?= $culto->totalPresentes ?></td>
                                <td>
                                    <span class="status-badge <?= $culto->status === 'agendado' ? 'is-ativo' : 'is-inativo' ?>">
                                        <?= $statusLabels[$culto->status] ?? $culto->status ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
