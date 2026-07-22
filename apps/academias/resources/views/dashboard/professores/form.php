<?php

use Academias\Core\Csrf;
use Academias\Models\Professor;

/**
 * @var array $config
 * @var Professor|null $professor
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $professor !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/professores/' . $professor->id : $basePath . '/dashboard/professores';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Equipe</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar professor' : 'Novo professor' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/professores" class="btn-k btn-k-outline">Voltar</a>
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
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $professor->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <div class="form-field">
                    <label for="especialidade">Especialidade</label>
                    <input type="text" id="especialidade" name="especialidade" value="<?= htmlspecialchars($old['especialidade'] ?? $professor->especialidade ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Musculação, funcional, natação...">
                </div>
                <div class="form-field">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($old['telefone'] ?? $professor->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
                <div class="form-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? $professor->email ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="professor@email.com">
                </div>
                <?php if ($isEdit): ?>
                    <div class="form-field crud-checkbox-field">
                        <input type="checkbox" id="ativo" name="ativo" value="1" <?= $professor->ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;">Professor ativo</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/professores" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
