<?php
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
    <meta name="description" content="KADOSYS Igrejas - sistema de gestao completo para igrejas: membros, ministerios, financeiro, agenda e muito mais.">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/[email protected]/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/[email protected]/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/landing.css">
</head>
<body>

<header class="landing-navbar">
    <div class="container">
        <a href="<?= $basePath ?>/" class="brand-mark">
            <span class="seal">K</span>
            KADOSYS <span style="color: var(--gold);">Igrejas</span>
        </a>

        <nav class="landing-nav-links" data-nav-links>
            <a href="#sobre">Sobre</a>
            <a href="#recursos">Recursos</a>
            <a href="#funcionalidades">Funcionalidades</a>
            <a href="#planos">Planos</a>
            <a href="#faq">FAQ</a>
        </nav>

        <div class="landing-nav-actions">
            <a href="<?= $basePath ?>/login" class="btn-kadosys btn-outline-kadosys">Acessar o sistema</a>
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
                    KADOSYS Igrejas
                </div>
                <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 280px; line-height: 1.6;">
                    Gestao completa para a administracao da sua igreja, em um so lugar.
                </p>
            </div>
            <div class="footer-col">
                <h5>Produto</h5>
                <ul>
                    <li><a href="#sobre">Sobre o sistema</a></li>
                    <li><a href="#recursos">Recursos</a></li>
                    <li><a href="#planos">Planos</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h5>Suporte</h5>
                <ul>
                    <li><a href="#faq">Perguntas frequentes</a></li>
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
            <span>KADOSYS Igrejas - modulo da plataforma KADOSYS</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/[email protected]/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $basePath ?>/assets/js/landing.js"></script>
</body>
</html>
