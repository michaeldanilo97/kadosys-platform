<?php

use Food\Core\Csrf;
use Food\Models\Produto;

/**
 * @var array $config
 * @var array<int, Produto> $produtos
 * @var array<int, string> $categoriasPorId
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';

$labelStatus = [
    Produto::STATUS_ATIVO => 'Ativo',
    Produto::STATUS_PAUSADO => 'Pausado',
    Produto::STATUS_INATIVO => 'Inativo',
];

$badgeStatus = [
    Produto::STATUS_ATIVO => 'ok',
    Produto::STATUS_PAUSADO => 'dim',
    Produto::STATUS_INATIVO => 'danger',
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Catálogo</p>
            <h1 class="dashboard-title">Produtos</h1>
            <p class="dash-page-subtitle"><?= $total ?> cadastrado<?= $total === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/produtos/novo" class="btn-k btn-k-grad">+ Novo produto</a>
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

    <div class="glass-card dash-panel">
        <form method="GET" action="<?= $basePath ?>/dashboard/produtos" class="crud-search">
            <input type="text" name="busca" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome ou código...">
            <button type="submit" class="btn-k btn-k-outline">Buscar</button>
        </form>

        <?php if ($produtos === []): ?>
            <p class="crud-empty">Nenhum produto encontrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Preço balcão</th>
                            <th>Custo</th>
                            <th>Margem</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td><?= htmlspecialchars($produto->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($produto->categoriaId !== null ? ($categoriasPorId[$produto->categoriaId] ?? '-') : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim">R$ <?= number_format($produto->precoBalcao, 2, ',', '.') ?></td>
                                <td class="text-dim">R$ <?= number_format($produto->custoTotal, 2, ',', '.') ?></td>
                                <td>
                                    <span class="status-badge <?= $produto->margemPercentual < 15 ? 'danger' : 'ok' ?>"><?= number_format($produto->margemPercentual, 1, ',', '.') ?>%</span>
                                </td>
                                <td><span class="status-badge <?= $badgeStatus[$produto->status] ?? 'dim' ?>"><?= $labelStatus[$produto->status] ?? $produto->status ?></span></td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/produtos/<?= $produto->id ?>/ficha-tecnica" class="crud-icon-btn" title="Ficha técnica"><i class="bi bi-clipboard2-data"></i></a>
                                    <a href="<?= $basePath ?>/dashboard/produtos/<?= $produto->id ?>/editar" class="crud-icon-btn" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/produtos/<?= $produto->id ?>/excluir" data-confirm="Excluir este produto?">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Excluir"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                </td>
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
                            <a href="<?= $basePath ?>/dashboard/produtos?pagina=<?= $p ?>&busca=<?= urlencode($search) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
