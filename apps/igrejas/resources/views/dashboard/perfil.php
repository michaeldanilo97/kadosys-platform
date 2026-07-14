<?php

use Igrejas\Core\View;
use Igrejas\Models\User;

/**
 * @var array $config
 * @var User $user
 * @var string|null $success
 * @var array $errors
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Meu perfil</h1>
        <p class="dash-page-subtitle">Foto, cargo e instrumento que aparecem pra todo mundo na tela Equipe.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/equipe" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

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
    <form method="POST" action="<?= $basePath ?>/dashboard/perfil" class="crud-form" enctype="multipart/form-data">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="crud-form-section">
            <h3><i class="bi bi-person-circle"></i> Foto</h3>
            <div class="perfil-foto-atual">
                <?php if ($user->fotoPath !== null): ?>
                    <img src="<?= $basePath ?>/<?= htmlspecialchars($user->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Foto atual">
                <?php else: ?>
                    <span class="equipe-card-inicial"><?= htmlspecialchars(mb_strtoupper(mb_substr($user->name, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <div class="crud-field">
                    <label for="foto">Trocar foto</label>
                    <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp">
                    <span class="auth-field-hint">PNG, JPG ou WEBP, até 5MB.</span>
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-music-note-list"></i> Cargo na equipe</h3>
            <div class="crud-form-grid">
                <div class="crud-field">
                    <label for="cargo">Cargo</label>
                    <select id="cargo" name="cargo" data-cargo-select>
                        <?php foreach (User::CARGOS as $cargoSlug => $cargoInfo): ?>
                            <option value="<?= $cargoSlug ?>" <?= $user->cargo === $cargoSlug ? 'selected' : '' ?>><?= htmlspecialchars($cargoInfo['label'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crud-field" data-instrumento-field <?= $user->cargo !== User::CARGO_MUSICO ? 'hidden' : '' ?>>
                    <label for="instrumento">Instrumento</label>
                    <select id="instrumento" name="instrumento">
                        <option value="">Nenhum</option>
                        <?php foreach (User::INSTRUMENTOS as $instrumentoSlug => $instrumentoInfo): ?>
                            <option value="<?= $instrumentoSlug ?>" <?= $user->instrumento === $instrumentoSlug ? 'selected' : '' ?>><?= $instrumentoInfo['emoji'] ?> <?= htmlspecialchars($instrumentoInfo['label'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> Salvar perfil
            </button>
        </div>
    </form>
</div>

<script src="<?= $basePath ?>/assets/js/usuario-form.js?v=<?= View::assetVersion('assets/js/usuario-form.js') ?>"></script>
