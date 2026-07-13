<?php
/**
 * @var array $config
 * @var array<int, \Igrejas\Models\PatrimonioBem> $bens
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var float $valorTotalAtivo
 * @var int $countAtivos
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';

$categoriaLabels = [
    'imovel' => 'Imóvel', 'veiculo' => 'Veículo', 'equipamento' => 'Equipamento',
    'mobiliario' => 'Mobiliário', 'eletronico' => 'Eletrônico', 'outro' => 'Outro',
];
$statusLabels = ['ativo' => 'Ativo', 'manutencao' => 'Em manutenção', 'baixado' => 'Baixado'];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Patrimônio</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> bem<?= $total === 1 ? '' : 's' ?> cadastrado<?= $total === 1 ? '' : 's' ?>.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/patrimonio/novo" class="btn-k btn-k-grad">
            <i class="bi bi-plus-lg"></i> Novo bem
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon blue"><i class="bi bi-building"></i></div>
        </div>
        <div class="value"><?= $countAtivos ?></div>
        <div class="label">Bens ativos</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon green"><i class="bi bi-cash-stack"></i></div>
        </div>
        <div class="value">R$ <?= number_format($valorTotalAtivo, 2, ',', '.') ?></div>
        <div class="label">Valor estimado total</div>
    </div>
</div>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/patrimonio" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por nome, número de patrimônio ou local..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/patrimonio" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($bens === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-building"></i></div>
            <h2><?= $search !== '' ? 'Nenhum bem encontrado' : 'Nenhum bem cadastrado ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro termo.'
                    : 'Comece cadastrando o primeiro bem, imóvel ou equipamento da igreja.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/patrimonio/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-plus-lg"></i> Cadastrar bem
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Bem</th>
                        <th>Categoria</th>
                        <th>Número</th>
                        <th>Local</th>
                        <th>Valor estimado</th>
                        <th>Status</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bens as $bem): ?>
                        <tr>
                            <td>
                                <div class="crud-person">
                                    <span class="crud-avatar">
                                        <?= htmlspecialchars(mb_strtoupper(mb_substr($bem->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span><?= htmlspecialchars($bem->nome, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td><?= $categoriaLabels[$bem->categoria] ?? $bem->categoria ?></td>
                            <td>
                                <?= $bem->numeroPatrimonio
                                    ? htmlspecialchars($bem->numeroPatrimonio, ENT_QUOTES, 'UTF-8')
                                    : '<span class="crud-text-dim">&mdash;</span>' ?>
                            </td>
                            <td>
                                <?= $bem->local
                                    ? htmlspecialchars($bem->local, ENT_QUOTES, 'UTF-8')
                                    : '<span class="crud-text-dim">&mdash;</span>' ?>
                            </td>
                            <td>
                                <?= $bem->valorEstimado !== null
                                    ? 'R$ ' . number_format($bem->valorEstimado, 2, ',', '.')
                                    : '<span class="crud-text-dim">&mdash;</span>' ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $bem->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $statusLabels[$bem->status] ?? $bem->status ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a
                                    href="<?= $basePath ?>/dashboard/patrimonio/<?= $bem->id ?>/editar"
                                    class="crud-icon-btn"
                                    aria-label="Editar <?= htmlspecialchars($bem->nome, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/dashboard/patrimonio/<?= $bem->id ?>/excluir"
                                    data-confirm="Remover o bem &quot;<?= htmlspecialchars($bem->nome, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button
                                        type="submit"
                                        class="crud-icon-btn danger"
                                        aria-label="Excluir <?= htmlspecialchars($bem->nome, ENT_QUOTES, 'UTF-8') ?>"
                                    >
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
                        href="<?= $basePath ?>/dashboard/patrimonio?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
