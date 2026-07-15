<?php

use Igrejas\Models\Tenant;

/**
 * @var array $config
 * @var string $email
 * @var array<int, Tenant> $igrejas
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="auth-form-card auth-form-card-wide">
    <div class="eyebrow">Acesso restrito</div>
    <h1>Qual igreja?</h1>
    <p class="subtitle">
        Encontramos mais de uma igreja cadastrada com o e-mail
        <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
        Selecione em qual você quer entrar.
    </p>

    <ul class="auth-igreja-lista">
        <?php foreach ($igrejas as $igreja): ?>
            <?php // Tenant::subdominio ja guarda o host completo (ex.: "ijpm.kadosys.com.br"), nao so o slug - ver Provisionador::provisionar(). ?>
            <?php $url = 'https://' . $igreja->subdominio . '/login?email=' . urlencode($email); ?>
            <li>
                <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="auth-igreja-item">
                    <span class="auth-igreja-item-icone"><i class="bi bi-building"></i></span>
                    <span class="auth-igreja-item-texto">
                        <span class="auth-igreja-item-nome"><?= htmlspecialchars($igreja->nomeIgreja, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="auth-igreja-item-sub"><?= htmlspecialchars($igreja->subdominio, ENT_QUOTES, 'UTF-8') ?></span>
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
