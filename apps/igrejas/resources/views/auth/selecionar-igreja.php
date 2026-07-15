<?php

/**
 * @var array $config
 * @var string $email
 * @var array<int, array{nome: string, subtitulo: string, url: string}> $opcoes
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="auth-form-card auth-form-card-wide">
    <div class="eyebrow">Acesso restrito</div>
    <h1>Qual igreja?</h1>
    <p class="subtitle">
        Encontramos mais de uma conta cadastrada com o e-mail
        <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
        Selecione em qual você quer entrar.
    </p>

    <ul class="auth-igreja-lista">
        <?php foreach ($opcoes as $opcao): ?>
            <li>
                <a href="<?= htmlspecialchars($opcao['url'], ENT_QUOTES, 'UTF-8') ?>" class="auth-igreja-item">
                    <span class="auth-igreja-item-icone"><i class="bi bi-building"></i></span>
                    <span class="auth-igreja-item-texto">
                        <span class="auth-igreja-item-nome"><?= htmlspecialchars($opcao['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="auth-igreja-item-sub"><?= htmlspecialchars($opcao['subtitulo'], ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                    <i class="bi bi-arrow-right auth-igreja-item-seta"></i>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <a href="<?= $basePath ?>/login" class="auth-back-link">
        <i class="bi bi-arrow-left"></i> Usar outro e-mail
    </a>
</div>
