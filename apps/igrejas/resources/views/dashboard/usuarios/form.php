<?php

use Igrejas\Models\User;

/**
 * @var array $config
 * @var User|null $usuarioEditado
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $usuarioEditado !== null;

$name = $old['name'] ?? $usuarioEditado->name ?? '';
$email = $old['email'] ?? $usuarioEditado->email ?? '';
$role = $old['role'] ?? $usuarioEditado->role ?? User::ROLE_USUARIO;
$active = array_key_exists('active', $old) ? !empty($old['active']) : ($usuarioEditado->active ?? true);

$actionUrl = $isEdit
    ? $basePath . '/dashboard/usuarios/' . $usuarioEditado->id
    : $basePath . '/dashboard/usuarios';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar usuario' : 'Novo usuario' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize os dados de ' . htmlspecialchars($usuarioEditado->name, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados para liberar o acesso de um novo usuario.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/usuarios" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?php if ($errors !== []): ?>
    <div class="crud-alert error">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="dash-panel">
    <form method="POST" action="<?= $actionUrl ?>" class="crud-form">
        <?= $csrf ?>

        <div class="crud-form-section">
            <h3><i class="bi bi-person-badge"></i> Dados do usuario</h3>
            <div class="crud-form-grid">
                <div class="crud-field">
                    <label for="name">Nome *</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome completo" required autofocus>
                </div>
                <div class="crud-field">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" placeholder="voce@email.com" required>
                </div>
                <div class="crud-field">
                    <label for="role">Papel *</label>
                    <select id="role" name="role">
                        <option value="<?= User::ROLE_USUARIO ?>" <?= $role === User::ROLE_USUARIO ? 'selected' : '' ?>>Usuario</option>
                        <option value="<?= User::ROLE_ADMIN ?>" <?= $role === User::ROLE_ADMIN ? 'selected' : '' ?>>Administrador</option>
                    </select>
                    <span class="auth-field-hint">Administrador ve tudo e gerencia usuarios/plano. Usuario ve os modulos do plano, exceto Usuarios, Permissoes e Configuracoes - pode ser restrito ainda mais em Permissoes.</span>
                </div>
                <?php if ($isEdit): ?>
                    <div class="crud-field">
                        <label for="active">Status</label>
                        <select id="active" name="active">
                            <option value="1" <?= $active ? 'selected' : '' ?>>Ativo</option>
                            <option value="0" <?= !$active ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="crud-field">
                    <label for="password"><?= $isEdit ? 'Nova senha' : 'Senha *' ?></label>
                    <input type="password" id="password" name="password" placeholder="<?= $isEdit ? 'Deixe em branco para manter a atual' : 'Minimo 8 caracteres' ?>" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
                </div>
                <div class="crud-field">
                    <label for="password_confirmacao">Confirmar senha</label>
                    <input type="password" id="password_confirmacao" name="password_confirmacao" placeholder="Repita a senha" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/usuarios" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alteracoes' : 'Cadastrar usuario' ?>
            </button>
        </div>
    </form>
</div>

<?php if ($isEdit && $usuarioEditado->role === User::ROLE_USUARIO): ?>
    <p class="crud-text-dim" style="margin-top: 1rem;">
        Pra restringir os modulos que <?= htmlspecialchars($usuarioEditado->name, ENT_QUOTES, 'UTF-8') ?> pode acessar,
        va em <a href="<?= $basePath ?>/dashboard/permissoes/<?= $usuarioEditado->id ?>/editar">Permissoes</a> (plano Premium).
    </p>
<?php endif; ?>
