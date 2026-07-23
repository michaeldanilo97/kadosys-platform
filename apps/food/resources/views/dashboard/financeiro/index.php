<?php

use Food\Models\ContaPagar;
use Food\Models\ContaReceber;
use Food\Models\FinanceiroLancamento;

/**
 * @var array $config
 * @var array{receitas: float, despesas: float, saldo: float} $resumoDia
 * @var array{receitas: float, despesas: float, saldo: float} $resumoMes
 * @var float $totalContasPagarVencidas
 * @var float $totalContasReceberVencidas
 * @var array<int, ContaPagar> $proximasContasPagar
 * @var array<int, ContaReceber> $proximasContasReceber
 * @var array<int, FinanceiroLancamento> $lancamentos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $tipo
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$moeda = static fn (float $valor): string => 'R$ ' . number_format($valor, 2, ',', '.');

$labelFormaPagamento = [
    'dinheiro' => 'Dinheiro',
    'pix' => 'Pix',
    'cartao_credito' => 'Cartão de crédito',
    'cartao_debito' => 'Cartão de débito',
    'outro' => 'Outro',
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Gestão</p>
            <h1 class="dashboard-title">Financeiro</h1>
            <p class="dash-page-subtitle">Receitas, despesas, contas a pagar e a receber.</p>
        </div>
        <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
            <a href="<?= $basePath ?>/dashboard/financeiro/contas-a-pagar" class="btn-k btn-k-outline">Contas a pagar</a>
            <a href="<?= $basePath ?>/dashboard/financeiro/contas-a-receber" class="btn-k btn-k-outline">Contas a receber</a>
            <a href="<?= $basePath ?>/dashboard/financeiro/centros-custo" class="btn-k btn-k-outline">Centros de custo</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="kpi-grid">
        <div class="glass-card kpi-card">
            <p class="kpi-label">Receitas hoje</p>
            <p class="kpi-valor"><?= $moeda($resumoDia['receitas']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Despesas hoje</p>
            <p class="kpi-valor"><?= $moeda($resumoDia['despesas']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Saldo do mês</p>
            <p class="kpi-valor"><?= $moeda($resumoMes['saldo']) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Contas a pagar vencidas</p>
            <p class="kpi-valor"><?= $moeda($totalContasPagarVencidas) ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Contas a receber vencidas</p>
            <p class="kpi-valor"><?= $moeda($totalContasReceberVencidas) ?></p>
        </div>
    </div>

    <div class="pdv-grid-layout">
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Próximas contas a pagar</h2>
            </div>
            <?php if ($proximasContasPagar === []): ?>
                <p class="crud-empty">Nenhuma conta pendente.</p>
            <?php else: ?>
                <ul class="pdv-carrinho-itens">
                    <?php foreach ($proximasContasPagar as $conta): ?>
                        <li>
                            <div>
                                <strong><?= htmlspecialchars($conta->descricao, ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="text-dim"><?= (new DateTimeImmutable($conta->vencimento))->format('d/m/Y') ?><?= $conta->estaVencida() ? ' · vencida' : '' ?></span>
                            </div>
                            <span><?= $moeda($conta->valor) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Próximas contas a receber</h2>
            </div>
            <?php if ($proximasContasReceber === []): ?>
                <p class="crud-empty">Nenhuma conta pendente.</p>
            <?php else: ?>
                <ul class="pdv-carrinho-itens">
                    <?php foreach ($proximasContasReceber as $conta): ?>
                        <li>
                            <div>
                                <strong><?= htmlspecialchars($conta->descricao, ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="text-dim"><?= (new DateTimeImmutable($conta->vencimento))->format('d/m/Y') ?><?= $conta->estaVencida() ? ' · vencida' : '' ?></span>
                            </div>
                            <span><?= $moeda($conta->valor) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Lançamentos</h2>
        </div>
        <form method="GET" action="<?= $basePath ?>/dashboard/financeiro" class="crud-search">
            <select name="tipo">
                <option value="">Todos os tipos</option>
                <option value="receita" <?= $tipo === 'receita' ? 'selected' : '' ?>>Receitas</option>
                <option value="despesa" <?= $tipo === 'despesa' ? 'selected' : '' ?>>Despesas</option>
            </select>
            <button type="submit" class="btn-k btn-k-outline">Filtrar</button>
        </form>

        <?php if ($lancamentos === []): ?>
            <p class="crud-empty">Nenhum lançamento encontrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Pagamento</th>
                            <th>Valor</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lancamentos as $lancamento): ?>
                            <tr>
                                <td class="text-dim"><?= (new DateTimeImmutable($lancamento->dataLancamento))->format('d/m/Y') ?></td>
                                <td><span class="status-badge <?= $lancamento->tipo === FinanceiroLancamento::TIPO_RECEITA ? 'ok' : 'danger' ?>"><?= $lancamento->tipo === FinanceiroLancamento::TIPO_RECEITA ? 'Receita' : 'Despesa' ?></span></td>
                                <td class="text-dim"><?= htmlspecialchars($lancamento->categoria ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $labelFormaPagamento[$lancamento->formaPagamento] ?? $lancamento->formaPagamento ?></td>
                                <td><?= $moeda($lancamento->valor) ?></td>
                                <td class="text-dim"><?= htmlspecialchars($lancamento->descricao ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($lastPage > 1): ?>
                <div class="crud-pagination">
                    <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                        <?php if ($p === $page): ?>
                            <span class="atual"><?= $p ?></span>
                        <?php else: ?>
                            <a href="<?= $basePath ?>/dashboard/financeiro?pagina=<?= $p ?>&tipo=<?= urlencode($tipo) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
