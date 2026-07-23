<?php

use Food\Core\Csrf;
use Food\Models\Fornecedor;

/**
 * @var array $config
 * @var array<int, Fornecedor> $fornecedores
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$fornecedorSelecionado = (string) ($old['fornecedor_id'] ?? '');
$dataHoje = (new DateTimeImmutable('today'))->format('Y-m-d');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Compras</p>
            <h1 class="dashboard-title">Nova compra</h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/compras" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="POST" action="<?= $basePath ?>/dashboard/compras">
            <?= Csrf::field() ?>

            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="fornecedor_id">Fornecedor</label>
                    <select id="fornecedor_id" name="fornecedor_id">
                        <option value="">Sem fornecedor</option>
                        <?php foreach ($fornecedores as $fornecedor): ?>
                            <option value="<?= $fornecedor->id ?>" <?= $fornecedorSelecionado === (string) $fornecedor->id ? 'selected' : '' ?>><?= htmlspecialchars($fornecedor->nome, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="data_compra">Data da compra</label>
                    <input type="date" id="data_compra" name="data_compra" value="<?= htmlspecialchars((string) ($old['data_compra'] ?? $dataHoje), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="frete">Frete</label>
                    <input type="text" id="frete" name="frete" value="<?= htmlspecialchars((string) ($old['frete'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00">
                </div>
                <div class="form-field crud-field-full">
                    <label for="observacao">Observação</label>
                    <textarea id="observacao" name="observacao" rows="2"><?= htmlspecialchars((string) ($old['observacao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <p class="dash-page-subtitle">Depois de criar a compra, você adiciona os itens (ingredientes) na tela seguinte.</p>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad">Criar compra</button>
                <a href="<?= $basePath ?>/dashboard/compras" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
