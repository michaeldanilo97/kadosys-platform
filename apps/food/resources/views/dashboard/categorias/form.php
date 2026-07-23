<?php

use Food\Core\Csrf;
use Food\Models\Categoria;

/**
 * @var array $config
 * @var Categoria|null $categoria
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $categoria !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/categorias/' . $categoria->id : $basePath . '/dashboard/categorias';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Catálogo</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar categoria' : 'Nova categoria' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/categorias" class="btn-k btn-k-outline">Voltar</a>
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
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $categoria->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <?php if ($isEdit): ?>
                    <div class="form-field crud-checkbox-field">
                        <input type="checkbox" id="ativo" name="ativo" value="1" <?= $categoria->ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;">Categoria ativa</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/categorias" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
