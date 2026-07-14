<?php

use Igrejas\Core\View;

/**
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 */

$basePath = $config['base_path'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'KADOSYS Igrejas', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="KADOSYS Igrejas - plataforma completa de gestão para igrejas: membros, ministérios, financeiro, agenda, projeção e muito mais, com sincronização em tempo real.">
    <script>document.documentElement.classList.add('js');</script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/landing.css?v=<?= View::assetVersion('assets/css/landing.css') ?>">
</head>
<body>

<div class="bg-aurora" aria-hidden="true"></div>
<div class="bg-grid" aria-hidden="true"></div>

<header class="landing-navbar">
    <div class="container">
        <a href="<?= $basePath ?>/" class="brand-mark">
            <span class="seal">K</span>
            <span class="text-gradient">KADOSYS</span>&nbsp;Igrejas
        </a>

        <nav class="landing-nav-links" data-nav-links>
            <a href="<?= $basePath ?>/#sobre">Sobre</a>
            <div class="landing-nav-dropdown" data-nav-dropdown>
                <button type="button" class="landing-nav-dropdown-btn" data-nav-dropdown-btn>
                    Recursos <i class="bi bi-chevron-down"></i>
                </button>
                <div class="landing-nav-dropdown-panel">
                    <?php foreach (\Igrejas\Controllers\RecursoController::MODULOS as $slug => $modulo): ?>
                        <a href="<?= $basePath ?>/recursos/<?= $slug ?>">
                            <span class="landing-nav-dropdown-label">
                                <i class="bi <?= htmlspecialchars($modulo['icon'], ENT_QUOTES, 'UTF-8') ?>"></i> <?= htmlspecialchars($modulo['title'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if (in_array($slug, ['louvores', 'projecao'], true)): ?>
                                <span class="landing-nav-dropdown-badge">Ao vivo</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="<?= $basePath ?>/#funcionalidades">Funcionalidades</a>
            <a href="<?= $basePath ?>/#planos">Planos</a>
            <a href="<?= $basePath ?>/#faq">FAQ</a>
        </nav>

        <div class="landing-nav-actions">
            <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-ghost">
                <i class="bi bi-gift"></i> Teste grátis
            </a>
            <a href="<?= $basePath ?>/login" class="btn-k btn-k-outline">Acessar o sistema</a>
            <button class="nav-toggle" type="button" data-nav-toggle aria-label="Abrir menu" aria-expanded="false">
                <i class="bi bi-list" style="font-size: 1.3rem;"></i>
            </button>
        </div>
    </div>
</header>

<main>
    <?= $content ?>
</main>

<footer class="landing-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="brand-mark" style="margin-bottom: 0.8rem;">
                    <span class="seal">K</span>
                    <span class="text-gradient">KADOSYS</span>&nbsp;Igrejas
                </div>
                <p class="footer-about">
                    Plataforma completa de gestão para igrejas. Tecnologia moderna,
                    automação e sincronização em tempo real a serviço da sua comunidade.
                </p>
            </div>
            <div class="footer-col">
                <h5>Produto</h5>
                <ul>
                    <li><a href="<?= $basePath ?>/#sobre">Sobre o sistema</a></li>
                    <li><a href="<?= $basePath ?>/#recursos">Recursos</a></li>
                    <li><a href="<?= $basePath ?>/#planos">Planos</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h5>Recursos</h5>
                <ul>
                    <?php foreach (array_slice(\Igrejas\Controllers\RecursoController::MODULOS, 0, 5, true) as $slug => $modulo): ?>
                        <li><a href="<?= $basePath ?>/recursos/<?= $slug ?>"><?= htmlspecialchars($modulo['title'], ENT_QUOTES, 'UTF-8') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="footer-col">
                <h5>Suporte</h5>
                <ul>
                    <li><a href="<?= $basePath ?>/#faq">Perguntas frequentes</a></li>
                    <li><a href="<?= $basePath ?>/login">Acessar o sistema</a></li>
                    <li><a href="<?= $basePath ?>/esqueci-senha">Recuperar senha</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h5>Empresa</h5>
                <ul>
                    <li><a href="https://kadosys.com.br">Site institucional</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> KADOSYS. Todos os direitos reservados.</span>
            <span>KADOSYS Igrejas &middot; modulo da plataforma KADOSYS</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $basePath ?>/assets/js/landing.js?v=<?= View::assetVersion('assets/js/landing.js') ?>"></script>
</body>
</html>
