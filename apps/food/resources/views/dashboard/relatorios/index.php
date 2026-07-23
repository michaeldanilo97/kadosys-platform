<?php

use Food\Core\View;
use Food\Models\Ingrediente;

/**
 * @var array $config
 * @var int $ano
 * @var int $mes
 * @var array{receita: float, despesa: float, custoDireto: float, lucroBruto: float, lucroLiquido: float} $resumo
 * @var array{quantidade: int, ticketMedio: float} $vendas
 * @var array{receitaIfood: float, comissaoEstimada: float} $comissaoIfood
 * @var array<int, array{produtoId: int, nome: string, quantidade: float, receita: float}> $produtosMaisVendidos
 * @var array<int, array{produtoId: int, nome: string, quantidade: float, receita: float, custo: float, lucro: float}> $produtosMaisLucrativos
 * @var int $clientesAtivos
 * @var int $clientesNovos
 * @var array<int, Ingrediente> $ingredientesEstoqueBaixo
 * @var array<int, array{mes: string, receitas: float, despesas: float, saldo: float}> $fluxoCaixaMensal
 */
$basePath = $config['base_path'] ?? '';
$moeda = static fn (float $valor): string => 'R$ ' . number_format($valor, 2, ',', '.');

$nomesMeses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];

$graficoDados = [
    'meses' => array_column($fluxoCaixaMensal, 'mes'),
    'receitas' => array_column($fluxoCaixaMensal, 'receitas'),
    'despesas' => array_column($fluxoCaixaMensal, 'despesas'),
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Gestão</p>
            <h1 class="dashboard-title">Relatórios</h1>
            <p class="dash-page-subtitle">DRE, produtos, clientes e estoque de <?= $nomesMeses[$mes] ?>/<?= $ano ?>.</p>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <form method="GET" action="<?= $basePath ?>/dashboard/relatorios" class="crud-search">
            <select name="mes">
                <?php foreach ($nomesMeses as $numero => $nome): ?>
                    <option value="<?= $numero ?>" <?= $mes === $numero ? 'selected' : '' ?>><?= $nome ?></option>
                <?php endforeach; ?>
            </select>
            <select name="ano">
                <?php for ($anoOpcao = (int) date('Y'); $anoOpcao >= (int) date('Y') - 3; $anoOpcao--): ?>
                    <option value="<?= $anoOpcao ?>" <?= $ano === $anoOpcao ? 'selected' : '' ?>><?= $anoOpcao ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn-k btn-k-outline">Ver período</button>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>DRE do período</h2>
        </div>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Receita</th>
                        <th>Custo direto (CMV)</th>
                        <th>Lucro bruto</th>
                        <th>Despesas</th>
                        <th>Lucro líquido</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= $moeda($resumo['receita']) ?></td>
                        <td class="text-dim">- <?= $moeda($resumo['custoDireto']) ?></td>
                        <td><?= $moeda($resumo['lucroBruto']) ?></td>
                        <td class="text-dim">- <?= $moeda($resumo['despesa']) ?></td>
                        <td><strong><?= $moeda($resumo['lucroLiquido']) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="glass-card kpi-card">
            <p class="kpi-label">Pedidos confirmados</p>
            <p class="kpi-valor"><?= $vendas['quantidade'] ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Ticket médio</p>
            <p class="kpi-valor"><?= $moeda($vendas['ticketMedio']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Clientes ativos</p>
            <p class="kpi-valor"><?= $clientesAtivos ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Clientes novos no período</p>
            <p class="kpi-valor"><?= $clientesNovos ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Receita iFood no período</p>
            <p class="kpi-valor"><?= $moeda($comissaoIfood['receitaIfood']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Comissão iFood estimada</p>
            <p class="kpi-valor"><?= $moeda($comissaoIfood['comissaoEstimada']) ?></p>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Fluxo de caixa (últimos 6 meses)</h2>
        </div>
        <div style="position: relative; height: 280px;">
            <canvas data-grafico-fluxo-caixa data-dados="<?= htmlspecialchars(json_encode($graficoDados, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"></canvas>
        </div>
    </div>

    <div class="pdv-grid-layout">
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Produtos mais vendidos</h2>
            </div>
            <?php if ($produtosMaisVendidos === []): ?>
                <p class="crud-empty">Nenhuma venda confirmada neste período.</p>
            <?php else: ?>
                <div class="crud-table-wrapper">
                    <table class="crud-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Qtd. vendida</th>
                                <th>Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtosMaisVendidos as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-dim"><?= rtrim(rtrim(number_format($item['quantidade'], 2, ',', '.'), '0'), ',') ?></td>
                                    <td><?= $moeda($item['receita']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Produtos mais lucrativos</h2>
            </div>
            <?php if ($produtosMaisLucrativos === []): ?>
                <p class="crud-empty">Nenhuma venda confirmada neste período.</p>
            <?php else: ?>
                <div class="crud-table-wrapper">
                    <table class="crud-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Receita</th>
                                <th>Custo</th>
                                <th>Lucro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtosMaisLucrativos as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-dim"><?= $moeda($item['receita']) ?></td>
                                    <td class="text-dim">- <?= $moeda($item['custo']) ?></td>
                                    <td><strong><?= $moeda($item['lucro']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Estoque baixo</h2>
        </div>
        <?php if ($ingredientesEstoqueBaixo === []): ?>
            <p class="crud-empty">Nenhum ingrediente com estoque baixo.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Estoque atual</th>
                            <th>Estoque mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredientesEstoqueBaixo as $ingrediente): ?>
                            <tr>
                                <td><?= htmlspecialchars($ingrediente->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><span class="status-badge danger"><?= rtrim(rtrim(number_format($ingrediente->estoqueAtual, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="text-dim"><?= rtrim(rtrim(number_format($ingrediente->estoqueMinimo, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="<?= $basePath ?>/assets/js/vendor/chart.umd.min.js?v=<?= View::assetVersion('assets/js/vendor/chart.umd.min.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/grafico-fluxo-caixa.js?v=<?= View::assetVersion('assets/js/grafico-fluxo-caixa.js') ?>"></script>
