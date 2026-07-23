<?php

use Food\Core\Csrf;
use Food\Models\Compra;
use Food\Models\CompraItem;
use Food\Models\Fornecedor;
use Food\Models\Ingrediente;

/**
 * @var array $config
 * @var Compra $compra
 * @var Fornecedor|null $fornecedor
 * @var array<int, CompraItem> $itens
 * @var array<int, Ingrediente> $ingredientes
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$baseUrl = $basePath . '/dashboard/compras/' . $compra->id;
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Compras</p>
            <h1 class="dashboard-title">Compra #<?= $compra->id ?></h1>
            <p class="dash-page-subtitle"><?= (new DateTimeImmutable($compra->dataCompra))->format('d/m/Y') ?><?= $fornecedor !== null ? ' · ' . htmlspecialchars($fornecedor->nome, ENT_QUOTES, 'UTF-8') : '' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/compras" class="btn-k btn-k-outline">Voltar</a>
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
        <div class="dash-panel-head">
            <h2>Resumo</h2>
        </div>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Frete</th>
                        <th>Valor total</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>R$ <?= number_format($compra->frete, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($compra->valorTotal, 2, ',', '.') ?></td>
                        <td class="text-dim"><?= htmlspecialchars($compra->observacao ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Itens da compra</h2>
        </div>

        <?php if ($ingredientes === []): ?>
            <p class="crud-empty">Cadastre ingredientes antes de adicionar itens.</p>
        <?php else: ?>
            <form method="POST" action="<?= $baseUrl ?>/itens" class="crud-form-grid" style="margin-bottom: 1.5rem;">
                <?= Csrf::field() ?>
                <div class="form-field">
                    <label for="ingrediente_id">Ingrediente</label>
                    <select id="ingrediente_id" name="ingrediente_id" required>
                        <?php foreach ($ingredientes as $ingrediente): ?>
                            <option value="<?= $ingrediente->id ?>"><?= htmlspecialchars($ingrediente->nome, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="quantidade">Quantidade</label>
                    <input type="text" id="quantidade" name="quantidade" placeholder="0,000" required>
                </div>
                <div class="form-field">
                    <label for="preco_unitario">Preço unitário</label>
                    <input type="text" id="preco_unitario" name="preco_unitario" placeholder="0,00" required>
                </div>
                <div class="form-field">
                    <label for="validade">Validade (opcional)</label>
                    <input type="date" id="validade" name="validade">
                </div>
                <div class="form-field" style="align-self: flex-end;">
                    <button type="submit" class="btn-k btn-k-grad">+ Adicionar item</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($itens === []): ?>
            <p class="crud-empty">Nenhum item nesta compra ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Quantidade</th>
                            <th>Preço unitário</th>
                            <th>Subtotal</th>
                            <th>Validade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item->ingredienteNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= rtrim(rtrim(number_format($item->quantidade, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($item->unidade, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim">R$ <?= number_format($item->precoUnitario, 4, ',', '.') ?></td>
                                <td>R$ <?= number_format($item->subtotal, 2, ',', '.') ?></td>
                                <td class="text-dim"><?= $item->validade !== null ? (new DateTimeImmutable($item->validade))->format('d/m/Y') : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
