<?php
/**
 * @var array $config
 * @var string $csrf
 * @var string|null $status
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="auth-form-card">
    <div class="eyebrow">Recuperacao de acesso</div>
    <h1>Esqueci minha senha</h1>
    <p class="subtitle">Informe seu e-mail de acesso. Enviaremos as instrucoes de recuperacao.</p>

    <?php if (!empty($status)): ?>
        <div class="auth-alert success"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/esqueci-senha" novalidate>
        <?= $csrf ?>

        <div class="auth-field">
            <label for="email">E-mail cadastrado</label>
            <input
                type="email"
                class="form-control"
                id="email"
                name="email"
                placeholder="seu@email.com"
                autocomplete="email"
                required
                autofocus
            >
        </div>

        <button type="submit" class="btn-k btn-k-grad">Enviar instrucoes <i class="bi bi-send"></i></button>
    </form>

    <a href="<?= $basePath ?>/login" class="auth-back-link">
        <i class="bi bi-arrow-left"></i> Voltar para o login
    </a>
</div>
