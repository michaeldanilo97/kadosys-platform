<?php

use Food\Core\View;
use Food\Models\ContaPagar;
use Food\Models\ContaReceber;
use Food\Models\Ingrediente;
use Food\Models\Pedido;
use Food\Models\User;

/**
 * @var array $config
 * @var User|null $user
 * @var \Food\Models\Restaurante|null $restaurante
 * @var float $receitaHoje
 * @var float $receitaSemana
 * @var float $receitaMes
 * @var float $receitaAno
 * @var array{receita: float, despesa: float, custoDireto: float, lucroBruto: float, lucroLiquido: float} $resumoMes
 * @var array{quantidade: int, ticketMedio: float} $vendasMes
 * @var array{receitaIfood: float, comissaoEstimada: float} $comissaoIfoodMes
 * @var array<string, int> $pedidosPorStatus
 * @var array<int, Ingrediente> $ingredientesEstoqueBaixo
 * @var float $totalContasPagarVencidas
 * @var float $totalContasReceberVencidas
 * @var int $clientesAtivos
 * @var int $clientesNovosMes
 * @var array<int, array{produtoId: int, nome: string, quantidade: float, receita: float}> $produtosMaisVendidosMes
 * @var array<int, array{mes: string, receitas: float, despesas: float, saldo: float}> $fluxoCaixaMensal
 */
$basePath = $config['base_path'] ?? '';
$moeda = static fn (float $valor): string => 'R$ ' . number_format($valor, 2, ',', '.');

$labelStatus = [
    Pedido::STATUS_MONTAGEM => 'Montagem',
    Pedido::STATUS_RECEBIDO => 'Recebido',
    Pedido::STATUS_EM_PREPARO => 'Em preparo',
    Pedido::STATUS_FINALIZADO => 'Finalizado',
    Pedido::STATUS_SAIU_PARA_ENTREGA => 'Saiu para entrega',
    Pedido::STATUS_ENTREGUE => 'Entregue',
];

$pedidosAtivos = array_sum(array_diff_key($pedidosPorStatus, [Pedido::STATUS_ENTREGUE => true]));
$totalContasVencidas = $totalContasPagarVencidas + $totalContasReceberVencidas;

$graficoDados = [
    'meses' => array_column($fluxoCaixaMensal, 'mes'),
    'receitas' => array_column($fluxoCaixaMensal, 'receitas'),
    'despesas' => array_column($fluxoCaixaMensal, 'despesas'),
];
?>
<main class="dashboard-main">
    <p class="dashboard-eyebrow">Painel</p>
    <h1 class="dashboard-title">Olá, <?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?> 👋</h1>

    <div class="kpi-grid">
        <div class="glass-card kpi-card">
            <p class="kpi-label">Receita hoje</p>
            <p class="kpi-valor"><?= $moeda($receitaHoje) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Receita na semana</p>
            <p class="kpi-valor"><?= $moeda($receitaSemana) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Receita no mês</p>
            <p class="kpi-valor"><?= $moeda($receitaMes) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Receita no ano</p>
            <p class="kpi-valor"><?= $moeda($receitaAno) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Lucro bruto do mês</p>
            <p class="kpi-valor"><?= $moeda($resumoMes['lucroBruto']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Lucro líquido do mês</p>
            <p class="kpi-valor"><?= $moeda($resumoMes['lucroLiquido']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Ticket médio do mês</p>
            <p class="kpi-valor"><?= $moeda($vendasMes['ticketMedio']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Pedidos ativos</p>
            <p class="kpi-valor"><?= $pedidosAtivos ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Clientes ativos</p>
            <p class="kpi-valor"><?= $clientesAtivos ?> <span class="text-dim" style="font-size: 0.85rem;">(+<?= $clientesNovosMes ?> no mês)</span></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Ingredientes com estoque baixo</p>
            <p class="kpi-valor"><?= count($ingredientesEstoqueBaixo) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Contas vencidas</p>
            <p class="kpi-valor"><?= $moeda($totalContasVencidas) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Comissão iFood estimada (mês)</p>
            <p class="kpi-valor"><?= $moeda($comissaoIfoodMes['comissaoEstimada']) ?></p>
        </div>
    </div>

    <div class="pdv-grid-layout">
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Fluxo de caixa (últimos 6 meses)</h2>
            </div>
            <div style="position: relative; height: 280px;">
                <canvas data-grafico-fluxo-caixa data-dados="<?= htmlspecialchars(json_encode($graficoDados, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"></canvas>
            </div>
        </div>

        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Pedidos por status</h2>
            </div>
            <ul class="pdv-carrinho-itens">
                <?php foreach ($labelStatus as $status => $label): ?>
                    <li>
                        <div><?= $label ?></div>
                        <span><?= $pedidosPorStatus[$status] ?? 0 ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="pdv-grid-layout">
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Produtos mais vendidos no mês</h2>
            </div>
            <?php if ($produtosMaisVendidosMes === []): ?>
                <p class="crud-empty">Nenhuma venda confirmada este mês ainda.</p>
            <?php else: ?>
                <ul class="pdv-carrinho-itens">
                    <?php foreach ($produtosMaisVendidosMes as $item): ?>
                        <li>
                            <div><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?> <span class="text-dim">(<?= rtrim(rtrim(number_format($item['quantidade'], 2, ',', '.'), '0'), ',') ?>x)</span></div>
                            <span><?= $moeda($item['receita']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Estoque baixo</h2>
            </div>
            <?php if ($ingredientesEstoqueBaixo === []): ?>
                <p class="crud-empty">Nenhum ingrediente com estoque baixo.</p>
            <?php else: ?>
                <ul class="pdv-carrinho-itens">
                    <?php foreach ($ingredientesEstoqueBaixo as $ingrediente): ?>
                        <li>
                            <div><?= htmlspecialchars($ingrediente->nome, ENT_QUOTES, 'UTF-8') ?></div>
                            <span class="status-badge danger"><?= rtrim(rtrim(number_format($ingrediente->estoqueAtual, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= $basePath ?>/dashboard/estoque" class="btn-k btn-k-outline" style="margin-top: 1rem;">Ver estoque</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Relatórios completos</h2>
        </div>
        <p>DRE, produtos mais lucrativos, clientes e mais - tudo com o mesmo período que você escolher.</p>
        <a href="<?= $basePath ?>/dashboard/relatorios" class="btn-k btn-k-grad">Ver relatórios</a>
    </div>
</main>

<script src="<?= $basePath ?>/assets/js/vendor/chart.umd.min.js?v=<?= View::assetVersion('assets/js/vendor/chart.umd.min.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/grafico-fluxo-caixa.js?v=<?= View::assetVersion('assets/js/grafico-fluxo-caixa.js') ?>"></script>
