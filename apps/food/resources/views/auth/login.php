<?php

/**
 * @var array $config
 * @var string $csrf
 * @var string|null $error
 * @var string|null $success
 * @var array $old
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="glass-card auth-card">
    <div class="auth-brand"><span class="text-gradient">KADOSYS</span> Food</div>
    <p class="auth-subtitle">Acesse o painel do seu restaurante.</p>

    <?php if (!empty($success)): ?>
        <div class="auth-alert success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="auth-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/login" novalidate>
        <?= $csrf ?>

        <div class="form-field">
            <label for="email">E-mail</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="seu@email.com"
                autocomplete="email"
                required
                autofocus
            >
        </div>

        <div class="form-field">
            <label for="password">Senha</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="••••••••"
                autocomplete="current-password"
                required
            >
        </div>

        <p style="margin: -0.5rem 0 1rem; text-align: right;">
            <a href="<?= $basePath ?>/esqueci-senha" style="font-size: 0.85rem; color: var(--gray-400);">Esqueceu a senha?</a>
        </p>

        <button type="submit" class="btn-k btn-k-grad" style="width:100%; margin-top: 0.5rem;">Entrar</button>
    </form>
</div>
