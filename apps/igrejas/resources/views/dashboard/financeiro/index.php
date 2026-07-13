<?php
/**
 * @var array $config
 * @var array<int, \Igrejas\Models\FinanceiroLancamento> $lancamentos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var array{tipo: string, categoria_id: string, mes: string, busca: string} $filtros
 * @var array{entradas: float, saidas: float, saldo: float} $totais
 * @var array<int, \Igrejas\Models\FinanceiroCategoria> $categorias
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';

$formasPagamento = [
    'dinheiro' => 'Dinheiro',
    'pix' => 'Pix',
    'cartao' => 'Cartão',
    'transferencia' => 'Transferência',
    'boleto' => 'Boleto',
    'outro' => 'Outro',
];

$queryPersistente = array_filter([
    'tipo' => $filtros['tipo'],
    'categoria_id' => $filtros['categoria_id'],
    'mes' => $filtros['mes'],
    'busca' => $filtros['busca'],
], static fn ($valor) => $valor !== '');
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Financeiro</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> lançamento<?= $total === 1 ? '' : 's' ?> no período filtrado.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/financeiro/categorias" class="btn-k btn-k-ghost">
            <i class="bi bi-tags"></i> Categorias
        </a>
        <a href="<?= $basePath ?>/dashboard/financeiro/novo" class="btn-k btn-k-grad">
            <i class="bi bi-plus-lg"></i> Novo lancamento
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon green"><i class="bi bi-arrow-down-circle"></i></div>
        </div>
        <div class="value">R$ <?= number_format($totais['entradas'], 2, ',', '.') ?></div>
        <div class="label">Entradas no período</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon red"><i class="bi bi-arrow-up-circle"></i></div>
        </div>
        <div class="value">R$ <?= number_format($totais['saidas'], 2, ',', '.') ?></div>
        <div class="label">Saídas no período</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon <?= $totais['saldo'] >= 0 ? 'blue' : 'red' ?>"><i class="bi bi-wallet2"></i></div>
        </div>
        <div class="value">R$ <?= number_format($totais['saldo'], 2, ',', '.') ?></div>
        <div class="label">Saldo do período</div>
    </div>
</div>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/financeiro" class="crud-filters">
        <input type="month" name="mes" value="<?= htmlspecialchars($filtros['mes'], ENT_QUOTES, 'UTF-8') ?>">
        <select name="tipo">
            <option value="">Entradas e saídas</option>
            <option value="entrada" <?= $filtros['tipo'] === 'entrada' ? 'selected' : '' ?>>Só entradas</option>
            <option value="saida" <?= $filtros['tipo'] === 'saida' ? 'selected' : '' ?>>Só saídas</option>
        </select>
        <select name="categoria_id">
            <option value="">Todas as categorias</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= $categoria->id ?>" <?= $filtros['categoria_id'] === (string) $categoria->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($categoria->nome, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="crud-search">
            <i class="bi bi-search"></i>
            <input type="search" name="busca" value="<?= htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por descrição...">
        </div>
        <button type="submit" class="btn-k btn-k-ghost"><i class="bi bi-funnel"></i> Filtrar</button>
        <a href="<?= $basePath ?>/dashboard/financeiro" class="btn-k btn-k-ghost">Limpar</a>
    </form>

    <?php if ($lancamentos === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-cash-coin"></i></div>
            <h2>Nenhum lançamento encontrado</h2>
            <p>Ajuste os filtros ou cadastre o primeiro lançamento do período.</p>
            <a href="<?= $basePath ?>/dashboard/financeiro/novo" class="btn-k btn-k-grad">
                <i class="bi bi-plus-lg"></i> Novo lancamento
            </a>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Tipo</th>
                        <th>Forma</th>
                        <th>Valor</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lancamentos as $lancamento): ?>
                        <tr>
                            <td><?= (new DateTimeImmutable($lancamento->dataLancamento))->format('d/m/Y') ?></td>
                            <td>
                                <?= htmlspecialchars($lancamento->descricao, ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($lancamento->membroNome !== null): ?>
                                    <div class="crud-text-dim" style="font-size: 0.78rem;"><?= htmlspecialchars($lancamento->membroNome, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $lancamento->categoriaNome
                                    ? htmlspecialchars($lancamento->categoriaNome, ENT_QUOTES, 'UTF-8')
                                    : '<span class="crud-text-dim">Sem categoria</span>' ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $lancamento->tipo === 'entrada' ? 'is-entrada' : 'is-saida' ?>">
                                    <?= $lancamento->tipo === 'entrada' ? 'Entrada' : 'Saída' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($formasPagamento[$lancamento->formaPagamento] ?? $lancamento->formaPagamento, ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight: 700; color: <?= $lancamento->tipo === 'entrada' ? 'var(--success)' : 'var(--danger)' ?>;">
                                <?= $lancamento->tipo === 'entrada' ? '+' : '-' ?> R$ <?= number_format($lancamento->valor, 2, ',', '.') ?>
                            </td>
                            <td class="actions-col">
                                <a
                                    href="<?= $basePath ?>/dashboard/financeiro/<?= $lancamento->id ?>/editar"
                                    class="crud-icon-btn"
                                    aria-label="Editar lançamento"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/dashboard/financeiro/<?= $lancamento->id ?>/excluir"
                                    data-confirm="Remover este lançamento?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="crud-icon-btn danger" aria-label="Excluir lançamento">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($lastPage > 1): ?>
            <div class="crud-pagination">
                <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                    <a
                        href="<?= $basePath ?>/dashboard/financeiro?<?= http_build_query(['pagina' => $i] + $queryPersistente) ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
