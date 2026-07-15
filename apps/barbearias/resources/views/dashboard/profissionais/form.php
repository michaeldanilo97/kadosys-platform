<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Profissional;

/**
 * @var array $config
 * @var Profissional|null $profissional
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $profissional !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/profissionais/' . $profissional->id : $basePath . '/dashboard/profissionais';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Equipe</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar profissional' : 'Novo profissional' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/profissionais" class="btn-k btn-k-outline">Voltar</a>
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
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $profissional->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <div class="form-field">
                    <label for="especialidade">Especialidade</label>
                    <input type="text" id="especialidade" name="especialidade" value="<?= htmlspecialchars($old['especialidade'] ?? $profissional->especialidade ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Corte masculino, barba">
                </div>
                <div class="form-field">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($old['telefone'] ?? $profissional->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
                <?php if ($isEdit): ?>
                    <div class="form-field crud-checkbox-field">
                        <input type="checkbox" id="ativo" name="ativo" value="1" <?= $profissional->ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;">Profissional ativo</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/profissionais" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
