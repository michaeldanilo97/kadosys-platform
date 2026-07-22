<?php

use Academias\Core\Csrf;
use Academias\Models\User;

/**
 * @var array $config
 * @var User $membro
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Equipe</p>
            <h1 class="dashboard-title">Editar acesso</h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/configuracoes" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/equipe/<?= $membro->id ?>">
            <?= Csrf::field() ?>

            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="name">Nome</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? $membro->name, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? $membro->email, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="role">Papel</label>
                    <select id="role" name="role">
                        <option value="usuario" <?= ($old['role'] ?? $membro->role) === 'usuario' ? 'selected' : '' ?>>Equipe</option>
                        <option value="admin" <?= ($old['role'] ?? $membro->role) === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="password">Nova senha</label>
                    <input type="password" id="password" name="password" minlength="8" placeholder="Deixe em branco pra manter a atual">
                </div>
                <div class="form-field crud-checkbox-field">
                    <input type="checkbox" id="active" name="active" value="1" <?= $membro->active ? 'checked' : '' ?>>
                    <label for="active" style="margin:0;">Acesso ativo</label>
                </div>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad">Salvar alterações</button>
                <a href="<?= $basePath ?>/dashboard/configuracoes" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
