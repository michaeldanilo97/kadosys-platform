<?php

use Igrejas\Controllers\RecursoController;

/**
 * @var array $config
 * @var string $slug
 * @var array{title:string, icon:string, tagline:string, intro:string, diferenciais:array, imagem:string, imagemSecundaria:?string, imagemAlt:string} $modulo
 * @var string $proximoSlug
 * @var array{title:string, icon:string, tagline:string} $proximoModulo
 */
$basePath = $config['base_path'] ?? '';
$imgBase = $basePath . '/assets/img/';
?>

<!-- HERO DO RECURSO -->
<section class="hero recurso-hero">
    <div class="container">
        <div class="hero-copy reveal">
            <a href="<?= $basePath ?>/#recursos" class="recurso-voltar"><i class="bi bi-arrow-left"></i> Todos os recursos</a>
            <span class="eyebrow"><i class="bi <?= htmlspecialchars($modulo['icon'], ENT_QUOTES, 'UTF-8') ?>"></i> Módulo <?= htmlspecialchars($modulo['title'], ENT_QUOTES, 'UTF-8') ?></span>
            <h1><?= htmlspecialchars($modulo['tagline'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="lead"><?= htmlspecialchars($modulo['intro'], ENT_QUOTES, 'UTF-8') ?></p>
            <div class="hero-actions">
                <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-grad">Testar grátis por 7 dias</a>
                <a href="<?= $basePath ?>/#planos" class="btn-k btn-k-outline">Ver planos e preços</a>
            </div>
        </div>
    </div>
</section>

<!-- SCREENSHOT -->
<section class="landing-section recurso-screenshot-section">
    <div class="container">
        <div class="recurso-screenshot-frame glass-card reveal">
            <img
                src="<?= $imgBase . $modulo['imagem'] ?>"
                alt="<?= htmlspecialchars($modulo['imagemAlt'], ENT_QUOTES, 'UTF-8') ?>"
                loading="lazy"
                class="recurso-screenshot-principal"
            >
            <?php if ($modulo['imagemSecundaria'] !== null): ?>
                <img
                    src="<?= $imgBase . $modulo['imagemSecundaria'] ?>"
                    alt="Controle sincronizado pelo tablet"
                    loading="lazy"
                    class="recurso-screenshot-secundaria"
                >
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- DIFERENCIAIS -->
<section class="landing-section alt" id="diferenciais">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Diferenciais</span>
            <h2 class="section-title">O que torna <span class="text-gradient"><?= htmlspecialchars($modulo['title'], ENT_QUOTES, 'UTF-8') ?></span> diferente</h2>
        </div>

        <div class="cards-grid">
            <?php foreach ($modulo['diferenciais'] as $diferencial): ?>
                <div class="feature-card glass-card reveal">
                    <div class="icon"><i class="bi <?= htmlspecialchars($diferencial['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                    <h3><?= htmlspecialchars($diferencial['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($diferencial['texto'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PROXIMO RECURSO -->
<section class="landing-section recurso-proximo-section">
    <div class="container">
        <a href="<?= $basePath ?>/recursos/<?= $proximoSlug ?>" class="recurso-proximo-card glass-card reveal">
            <span class="recurso-proximo-label">Conheça também</span>
            <span class="recurso-proximo-titulo">
                <i class="bi <?= htmlspecialchars($proximoModulo['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                <?= htmlspecialchars($proximoModulo['title'], ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span class="recurso-proximo-seta"><i class="bi bi-arrow-right"></i></span>
        </a>
    </div>
</section>

<!-- CTA FINAL -->
<section class="cta-final">
    <div class="container">
        <div class="cta-final-card glass-card reveal">
            <span class="eyebrow">Comece hoje</span>
            <h2 class="section-title">Leve a gestão da sua igreja para <span class="text-gradient">o futuro</span></h2>
            <p>Teste grátis por 7 dias ou acesse o sistema, se sua igreja já usa o KADOSYS.</p>
            <div class="cta-final-actions">
                <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-grad">Começar agora</a>
                <a href="<?= $basePath ?>/login" class="btn-k btn-k-outline">Acessar o sistema</a>
            </div>
        </div>
    </div>
</section>
