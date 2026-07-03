<?php

declare(strict_types=1);

use Igrejas\Core\Csrf;
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
    <title><?= htmlspecialchars($pageTitle ?? 'Painel da Plataforma - KADOSYS', ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/dashboard.css?v=<?= View::assetVersion('assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/crud.css?v=<?= View::assetVersion('assets/css/crud.css') ?>">
    <style>
        /* Painel da plataforma nao tem sidebar - area interna, so o
           dono do sistema acessa (ver PlataformaAuthMiddleware). */
        .dash-shell { display: block; min-height: 100vh; }
        .dash-main { margin-left: 0; }
        .dash-content { padding: 2rem; max-width: 1100px; margin: 0 auto; }
    </style>
</head>
<body class="dashboard-body">

<div class="dash-shell">
    <div class="dash-main">
        <header class="dash-topbar">
            <div class="dash-topbar-left">
                <a href="<?= $basePath ?>/plataforma/igrejas" class="dash-sidebar-brand" style="display:flex;align-items:center;gap:0.5rem;">
                    <span class="seal">K</span>
                    <span><span class="text-gradient">KADOSYS</span> - Plataforma</span>
                </a>
            </div>

            <div class="dash-topbar-right">
                <button class="topbar-icon-btn" data-theme-toggle aria-label="Alternar tema claro/escuro">
                    <i class="bi bi-sun" data-theme-icon></i>
                </button>

                <form method="POST" action="<?= $basePath ?>/plataforma/sair">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="dash-logout-btn">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </button>
                </form>
            </div>
        </header>

        <div class="dash-content">
            <?= $content ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $basePath ?>/assets/js/dashboard.js?v=<?= View::assetVersion('assets/js/dashboard.js') ?>"></script>
</body>
</html>
