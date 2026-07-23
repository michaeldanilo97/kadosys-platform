<?php

use Food\Core\Csrf;
use Food\Models\Ingrediente;

/**
 * @var array $config
 * @var array<int, Ingrediente> $ingredientes
 * @var array<int, string> $fornecedoresPorId
 * @var array<int, Ingrediente> $estoqueBaixo
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Catálogo</p>
            <h1 class="dashboard-title">Ingredientes</h1>
            <p class="dash-page-subtitle"><?= $total ?> cadastrado<?= $total === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/ingredientes/novo" class="btn-k btn-k-grad">+ Novo ingrediente</a>
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

    <?php if ($estoqueBaixo !== []): ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Estoque baixo</h2>
            </div>
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
                        <?php foreach ($estoqueBaixo as $ingrediente): ?>
                            <tr>
                                <td><?= htmlspecialchars($ingrediente->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="status-badge danger"><?= rtrim(rtrim(number_format($ingrediente->estoqueAtual, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="text-dim"><?= rtrim(rtrim(number_format($ingrediente->estoqueMinimo, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="GET" action="<?= $basePath ?>/dashboard/ingredientes" class="crud-search">
            <input type="text" name="busca" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome ou código...">
            <button type="submit" class="btn-k btn-k-outline">Buscar</button>
        </form>

        <?php if ($ingredientes === []): ?>
            <p class="crud-empty">Nenhum ingrediente encontrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Fornecedor</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredientes as $ingrediente): ?>
                            <tr>
                                <td><?= htmlspecialchars($ingrediente->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($ingrediente->categoria ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($ingrediente->fornecedorId !== null ? ($fornecedoresPorId[$ingrediente->fornecedorId] ?? '-') : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim">R$ <?= number_format($ingrediente->precoAtual, 2, ',', '.') ?> / <?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="status-badge <?= $ingrediente->estoqueAtual <= $ingrediente->estoqueMinimo ? 'danger' : 'ok' ?>"><?= rtrim(rtrim(number_format($ingrediente->estoqueAtual, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $ingrediente->ativo ? 'ok' : 'dim' ?>"><?= $ingrediente->ativo ? 'Ativo' : 'Inativo' ?></span>
                                </td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/ingredientes/<?= $ingrediente->id ?>/editar" class="crud-icon-btn" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/ingredientes/<?= $ingrediente->id ?>/excluir" data-confirm="Excluir este ingrediente?">
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
                            <a href="<?= $basePath ?>/dashboard/ingredientes?pagina=<?= $p ?>&busca=<?= urlencode($search) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
