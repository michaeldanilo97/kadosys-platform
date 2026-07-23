<?php

use Food\Models\Compra;

/**
 * @var array $config
 * @var array<int, Compra> $compras
 * @var array<int, string> $fornecedoresPorId
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Compras</p>
            <h1 class="dashboard-title">Compras</h1>
            <p class="dash-page-subtitle"><?= $total ?> registrada<?= $total === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/compras/nova" class="btn-k btn-k-grad">+ Nova compra</a>
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
        <?php if ($compras === []): ?>
            <p class="crud-empty">Nenhuma compra registrada ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Fornecedor</th>
                            <th>Frete</th>
                            <th>Valor total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compras as $compra): ?>
                            <tr>
                                <td><?= (new DateTimeImmutable($compra->dataCompra))->format('d/m/Y') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($compra->fornecedorId !== null ? ($fornecedoresPorId[$compra->fornecedorId] ?? '-') : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim">R$ <?= number_format($compra->frete, 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($compra->valorTotal, 2, ',', '.') ?></td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/compras/<?= $compra->id ?>" class="crud-icon-btn" title="Ver detalhes"><i class="bi bi-eye-fill"></i></a>
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
                            <a href="<?= $basePath ?>/dashboard/compras?pagina=<?= $p ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
