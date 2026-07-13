<?php

/**
 * @var array $config
 * @var string $csrf
 * @var string|null $error
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="auth-form-card">
    <div class="eyebrow">Acesso restrito - dono do sistema</div>
    <h1>Painel da Plataforma</h1>
    <p class="subtitle">Área interna de administração das igrejas provisionadas. Não é o login normal de uma igreja.</p>

    <?php if (!empty($error)): ?>
        <div class="auth-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/plataforma/entrar" novalidate>
        <?= $csrf ?>

        <div class="auth-field">
            <label for="chave">Chave mestra</label>
            <input
                type="password"
                class="form-control"
                id="chave"
                name="chave"
                placeholder="••••••••••••"
                autocomplete="off"
                required
                autofocus
            >
        </div>

        <button type="submit" class="btn-k btn-k-grad">Entrar <i class="bi bi-arrow-right"></i></button>
    </form>

    <a href="<?= $basePath ?>/" class="auth-back-link">
        <i class="bi bi-arrow-left"></i> Voltar para a página inicial
    </a>
</div>
