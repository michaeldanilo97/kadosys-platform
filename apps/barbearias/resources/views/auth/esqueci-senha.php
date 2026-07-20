<?php

/**
 * @var array $config
 * @var string $csrf
 * @var string|null $status
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="glass-card auth-card">
    <div class="auth-brand"><span class="text-gradient">KADOSYS</span> Barbearias</div>
    <p class="auth-subtitle">Informe seu e-mail de acesso. Enviaremos as instruções de recuperação.</p>

    <?php if (!empty($status)): ?>
        <div class="auth-alert success"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>
        <form method="POST" action="<?= $basePath ?>/esqueci-senha" novalidate>
            <?= $csrf ?>

            <div class="form-field">
                <label for="email">E-mail cadastrado</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="seu@email.com"
                    autocomplete="email"
                    required
                    autofocus
                >
            </div>

            <button type="submit" class="btn-k btn-k-grad" style="width:100%; margin-top: 0.5rem;">Enviar instruções</button>
        </form>
    <?php endif; ?>

    <p style="margin: 1.5rem 0 0; text-align: center;">
        <a href="<?= $basePath ?>/login" style="font-size: 0.85rem; color: var(--gray-400);">← Voltar para o login</a>
    </p>
</div>
