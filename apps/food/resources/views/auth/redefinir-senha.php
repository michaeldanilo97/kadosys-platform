<?php

/**
 * @var array $config
 * @var string $csrf
 * @var string $token
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="glass-card auth-card">
    <div class="auth-brand"><span class="text-gradient">KADOSYS</span> Food</div>
    <p class="auth-subtitle">Escolha sua nova senha.</p>

    <?php if ($errors !== []): ?>
        <div class="auth-alert error">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/redefinir-senha/<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>" novalidate>
        <?= $csrf ?>

        <div class="form-field">
            <label for="password">Nova senha</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="••••••••"
                autocomplete="new-password"
                required
                autofocus
            >
        </div>

        <div class="form-field">
            <label for="password_confirmation">Confirme a nova senha</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                placeholder="••••••••"
                autocomplete="new-password"
                required
            >
        </div>

        <button type="submit" class="btn-k btn-k-grad" style="width:100%; margin-top: 0.5rem;">Redefinir senha</button>
    </form>
</div>
