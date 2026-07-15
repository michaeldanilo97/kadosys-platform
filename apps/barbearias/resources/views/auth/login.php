<?php

/**
 * @var array $config
 * @var string $csrf
 * @var string|null $error
 * @var array $old
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="glass-card auth-card">
    <div class="auth-brand"><span class="text-gradient">KADOSYS</span> Barbearias</div>
    <p class="auth-subtitle">Acesse o painel da sua barbearia.</p>

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

        <button type="submit" class="btn-k btn-k-grad" style="width:100%; margin-top: 0.5rem;">Entrar</button>
    </form>
</div>
