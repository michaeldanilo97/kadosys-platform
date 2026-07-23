<?php

use Academias\Models\Academia;

/**
 * @var array $config
 * @var Academia $academia
 * @var string $csrf
 * @var array<int, string> $errors
 * @var array $old
 * @var string $next
 */
$basePath = $config['base_path'] ?? '';
$slug = htmlspecialchars($academia->slug, ENT_QUOTES, 'UTF-8');
$nextQuery = $next !== '' ? '?next=' . urlencode($next) : '';
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card">
        <div class="hero-eyebrow">Área do aluno</div>
        <h1><?= htmlspecialchars($academia->nome, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle">Confirme o telefone ou e-mail que a academia já cadastrou e crie sua senha.</p>

        <?php if ($errors !== []): ?>
            <div class="form-alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/minha-conta/<?= $slug ?>/cadastro<?= $nextQuery ?>">
            <?= $csrf ?>
            <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-field">
                <label for="identificador">Telefone ou e-mail cadastrado</label>
                <input type="text" id="identificador" name="identificador" value="<?= htmlspecialchars($old['identificador'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000" required autofocus>
            </div>
            <div class="form-field">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" minlength="8" placeholder="Mínimo 8 caracteres" required>
            </div>
            <div class="form-field">
                <label for="senha_confirmacao">Confirmar senha</label>
                <input type="password" id="senha_confirmacao" name="senha_confirmacao" minlength="8" required>
            </div>
            <button type="submit" class="btn-k btn-k-grad" style="width:100%; margin-top: 0.5rem;">Criar minha senha</button>
        </form>

        <p style="text-align:center; margin-top:1.5rem; font-size:0.85rem; color: var(--gray-400);">
            Já tem senha? <a href="<?= $basePath ?>/minha-conta/<?= $slug ?>/entrar<?= $nextQuery ?>">Entrar</a>
        </p>
    </div>
</div>
