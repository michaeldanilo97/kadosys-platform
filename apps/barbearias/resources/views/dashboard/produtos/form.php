<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Produto;

/**
 * @var array $config
 * @var Produto|null $produto
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $produto !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/produtos/' . $produto->id : $basePath . '/dashboard/produtos';
$precoValor = $old['preco'] ?? ($produto !== null ? number_format($produto->preco, 2, '.', '') : '');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Catálogo</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar produto' : 'Novo produto' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/produtos" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="POST" action="<?= $actionUrl ?>">
            <?= Csrf::field() ?>

            <div class="crud-form-grid">
                <div class="form-field crud-field-full">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $produto->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Pomada modeladora" required autofocus>
                </div>
                <div class="form-field">
                    <label for="preco">Preço (R$)</label>
                    <input type="text" id="preco" name="preco" inputmode="decimal" value="<?= htmlspecialchars((string) $precoValor, ENT_QUOTES, 'UTF-8') ?>" placeholder="35,00" required>
                </div>
                <div class="form-field">
                    <label for="estoque_atual">Estoque atual</label>
                    <input type="number" id="estoque_atual" name="estoque_atual" min="0" value="<?= htmlspecialchars((string) ($old['estoque_atual'] ?? $produto->estoqueAtual ?? 0), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="estoque_minimo">Estoque mínimo</label>
                    <input type="number" id="estoque_minimo" name="estoque_minimo" min="0" value="<?= htmlspecialchars((string) ($old['estoque_minimo'] ?? $produto->estoqueMinimo ?? 0), ENT_QUOTES, 'UTF-8') ?>" required>
                    <span class="form-field-hint">Abaixo desse valor, o produto entra no alerta de estoque baixo.</span>
                </div>
                <?php if ($isEdit): ?>
                    <div class="form-field crud-checkbox-field">
                        <input type="checkbox" id="ativo" name="ativo" value="1" <?= $produto->ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;">Produto ativo</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/produtos" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
