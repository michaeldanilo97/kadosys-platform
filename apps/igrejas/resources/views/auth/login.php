<?php

use Igrejas\Models\ConfiguracaoIgreja;

/**
 * @var array $config
 * @var string $csrf
 * @var string|null $error
 * @var array $old
 * @var ConfiguracaoIgreja|null $configuracao
 */
$basePath = $config['base_path'] ?? '';
$logoUrl = $configuracao?->logoPath ? $basePath . '/' . $configuracao->logoPath : null;
?>
<div class="auth-form-card">
    <?php if ($logoUrl !== null): ?>
        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo <?= htmlspecialchars($configuracao->nomeIgreja ?? '', ENT_QUOTES, 'UTF-8') ?>" class="auth-igreja-logo">
    <?php endif; ?>
    <div class="eyebrow">Acesso restrito</div>
    <h1><?= $configuracao?->nomeIgreja ? htmlspecialchars($configuracao->nomeIgreja, ENT_QUOTES, 'UTF-8') : 'Entrar' ?></h1>
    <p class="subtitle">Acesse o painel administrativo da sua igreja.</p>

    <?php if (!empty($error)): ?>
        <div class="auth-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/login" novalidate>
        <?= $csrf ?>

        <div class="auth-field">
            <label for="email">E-mail</label>
            <input
                type="email"
                class="form-control"
                id="email"
                name="email"
                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="seu@email.com"
                autocomplete="email"
                required
                autofocus
            >
        </div>

        <div class="auth-field">
            <label for="password">Senha</label>
            <input
                type="password"
                class="form-control"
                id="password"
                name="password"
                placeholder="••••••••"
                autocomplete="current-password"
                required
            >
        </div>

        <div class="auth-options">
            <label style="display:flex; align-items:center; gap:0.45rem; font-weight: 500;">
                <input type="checkbox" name="remember" value="1">
                Lembrar-me
            </label>
            <a href="<?= $basePath ?>/esqueci-senha">Esqueci minha senha</a>
        </div>

        <button type="submit" class="btn-k btn-k-grad">Entrar <i class="bi bi-arrow-right"></i></button>
    </form>

    <p class="auth-signup-hint">
        Ainda não tem uma conta? <a href="<?= $basePath ?>/cadastro">Cadastre-se</a>
    </p>

    <a href="<?= $basePath ?>/" class="auth-back-link">
        <i class="bi bi-arrow-left"></i> Voltar para a página inicial
    </a>
</div>
