<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Produto;

/**
 * @var array $config
 * @var array<int, Produto> $produtos
 * @var array<int, Produto> $estoqueBaixo
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var array<int, string> $formasPagamento
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';

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

    <?php if ($estoqueBaixo !== []): ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Estoque baixo</h2>
            </div>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Estoque atual</th>
                            <th>Estoque mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estoqueBaixo as $produto): ?>
                            <tr>
                                <td><?= htmlspecialchars($produto->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="status-badge danger"><?= $produto->estoqueAtual ?></span></td>
                                <td class="text-dim"><?= $produto->estoqueMinimo ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="GET" action="<?= $basePath ?>/dashboard/produtos" class="crud-search">
            <input type="text" name="busca" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome...">
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
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Status</th>
                            <th>Venda avulsa</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td><?= htmlspecialchars($produto->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim">R$ <?= number_format($produto->preco, 2, ',', '.') ?></td>
                                <td>
                                    <span class="status-badge <?= $produto->estoqueAtual <= $produto->estoqueMinimo ? 'danger' : 'ok' ?>"><?= $produto->estoqueAtual ?></span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $produto->ativo ? 'ok' : 'dim' ?>"><?= $produto->ativo ? 'Ativo' : 'Inativo' ?></span>
                                </td>
                                <td>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/produtos/<?= $produto->id ?>/vender" style="display:flex; gap:0.4rem; align-items:center;">
                                        <?= Csrf::field() ?>
                                        <input type="number" name="quantidade" min="1" value="1" style="width:64px; padding:0.4rem 0.5rem;" <?= $produto->estoqueAtual < 1 ? 'disabled' : '' ?>>
                                        <select name="forma_pagamento" style="padding:0.4rem 0.5rem; border-radius:8px; border:1px solid var(--glass-border); background:var(--input-bg); color:var(--text);" <?= $produto->estoqueAtual < 1 ? 'disabled' : '' ?>>
                                            <?php foreach ($formasPagamento as $forma): ?>
                                                <option value="<?= $forma ?>"><?= $labelFormaPagamento[$forma] ?? $forma ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn-k btn-k-sm btn-k-outline" <?= $produto->estoqueAtual < 1 ? 'disabled' : '' ?>>Vender</button>
                                    </form>
                                </td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/produtos/<?= $produto->id ?>/editar" class="crud-icon-btn" title="Editar">✏️</a>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/produtos/<?= $produto->id ?>/excluir" onsubmit="return confirm('Excluir este produto?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Excluir">🗑️</button>
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
