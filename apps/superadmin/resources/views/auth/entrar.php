<?php

/**
 * @var array $config
 * @var string $csrf
 * @var string|null $error
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="auth-card">
    <div class="auth-eyebrow">Acesso restrito - dono da plataforma</div>
    <div class="auth-brand"><span class="text-gradient">KADOSYS</span> Super Admin</div>
    <h1>Entrar</h1>
    <p class="auth-subtitle">Painel unico dos produtos KADOSYS (Igrejas, Barbearias e futuros). Chave mestra, sem usuario/senha por pessoa.</p>

    <?php if (!empty($error)): ?>
        <div class="auth-alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/entrar" novalidate>
        <?= $csrf ?>
        <div class="field">
            <label for="chave">Chave mestra</label>
            <input
                type="password"
                id="chave"
                name="chave"
                placeholder="••••••••••••"
                autocomplete="off"
                required
                autofocus
            >
        </div>
        <button type="submit" class="btn btn-primary">Entrar</button>
    </form>
</div>
