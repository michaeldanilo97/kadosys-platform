<?php

use Igrejas\Core\View;
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
$musico = array_key_exists('musico', $old) ? !empty($old['musico']) : ($usuarioEditado->musico ?? false);
$liderLouvor = array_key_exists('lider_louvor', $old) ? !empty($old['lider_louvor']) : ($usuarioEditado->liderLouvor ?? false);
$cargo = $old['cargo'] ?? $usuarioEditado->cargo ?? User::CARGO_MEMBRO;
$instrumento = $old['instrumento'] ?? $usuarioEditado->instrumento ?? '';

$actionUrl = $isEdit
    ? $basePath . '/dashboard/usuarios/' . $usuarioEditado->id
    : $basePath . '/dashboard/usuarios';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar usuário' : 'Novo usuário' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize os dados de ' . htmlspecialchars($usuarioEditado->name, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados para liberar o acesso de um novo usuário.' ?>
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
            <h3><i class="bi bi-person-badge"></i> Dados do usuário</h3>
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
                        <option value="<?= User::ROLE_USUARIO ?>" <?= $role === User::ROLE_USUARIO ? 'selected' : '' ?>>Usuário</option>
                        <option value="<?= User::ROLE_ADMIN ?>" <?= $role === User::ROLE_ADMIN ? 'selected' : '' ?>>Administrador</option>
                    </select>
                    <span class="auth-field-hint">Administrador vê tudo e gerencia usuários/plano. Usuário vê os módulos do plano, exceto Usuários, Permissões e Configurações - pode ser restrito ainda mais em Permissões.</span>
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
                    <label for="cargo">Cargo na equipe</label>
                    <select id="cargo" name="cargo" data-cargo-select>
                        <?php foreach (User::CARGOS as $cargoSlug => $cargoInfo): ?>
                            <option value="<?= $cargoSlug ?>" <?= $cargo === $cargoSlug ? 'selected' : '' ?>><?= htmlspecialchars($cargoInfo['label'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="auth-field-hint">Aparece como ícone na tela Equipe - não muda nenhuma permissão de acesso.</span>
                </div>
                <div class="crud-field" data-instrumento-field <?= $cargo !== User::CARGO_MUSICO ? 'hidden' : '' ?>>
                    <label for="instrumento">Instrumento</label>
                    <select id="instrumento" name="instrumento">
                        <option value="">Nenhum</option>
                        <?php foreach (User::INSTRUMENTOS as $instrumentoSlug => $instrumentoInfo): ?>
                            <option value="<?= $instrumentoSlug ?>" <?= $instrumento === $instrumentoSlug ? 'selected' : '' ?>><?= $instrumentoInfo['emoji'] ?> <?= htmlspecialchars($instrumentoInfo['label'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crud-field crud-field-full">
                    <label class="toggle-switch-field">
                        <input type="checkbox" name="musico" value="1" <?= $musico ? 'checked' : '' ?>>
                        <span class="toggle-switch"></span>
                        <span class="toggle-switch-label">
                            Músico
                            <span class="auth-field-hint">Libera automaticamente o acesso ao módulo Louvores (letras, cifras e tons), sem precisar mexer em Permissões.</span>
                        </span>
                    </label>
                </div>
                <div class="crud-field crud-field-full">
                    <label class="toggle-switch-field">
                        <input type="checkbox" name="lider_louvor" value="1" <?= $liderLouvor ? 'checked' : '' ?>>
                        <span class="toggle-switch"></span>
                        <span class="toggle-switch-label">
                            Líder de louvor
                            <span class="auth-field-hint">Além do acesso de músico, pode montar/reordenar a programação de culto (repertório) e avançar a música atual durante o Modo Culto - os demais músicos só acompanham e usam o chat.</span>
                        </span>
                    </label>
                </div>
                <div class="crud-field">
                    <label for="password"><?= $isEdit ? 'Nova senha' : 'Senha *' ?></label>
                    <input type="password" id="password" name="password" placeholder="<?= $isEdit ? 'Deixe em branco para manter a atual' : 'Mínimo 8 caracteres' ?>" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
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
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alterações' : 'Cadastrar usuário' ?>
            </button>
        </div>
    </form>
</div>

<script src="<?= $basePath ?>/assets/js/usuario-form.js?v=<?= View::assetVersion('assets/js/usuario-form.js') ?>"></script>

<?php if ($isEdit && $usuarioEditado->role === User::ROLE_USUARIO): ?>
    <p class="crud-text-dim" style="margin-top: 1rem;">
        Pra restringir os módulos que <?= htmlspecialchars($usuarioEditado->name, ENT_QUOTES, 'UTF-8') ?> pode acessar,
        vá em <a href="<?= $basePath ?>/dashboard/permissoes/<?= $usuarioEditado->id ?>/editar">Permissões</a> (plano Premium).
    </p>
<?php endif; ?>
