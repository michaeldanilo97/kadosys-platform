<?php

declare(strict_types=1);

use Igrejas\Core\Csrf;
use Igrejas\Core\View;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Plano;

/**
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 * @var string $activeMenu
 * @var array $breadcrumb
 * @var \Igrejas\Models\User|null $user
 * @var array $modules
 */
$basePath = $config['base_path'] ?? '';
$userName = $user?->name ?? 'Usuario';
$userInitial = mb_strtoupper(mb_substr($userName, 0, 1));
$planoAtual = ConfiguracaoIgreja::atual()->plano;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard - KADOSYS Igrejas', ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/dashboard.css?v=<?= View::assetVersion('assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/crud.css?v=<?= View::assetVersion('assets/css/crud.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/biblia-picker.css?v=<?= View::assetVersion('assets/css/biblia-picker.css') ?>">
</head>
<body class="dashboard-body">

<div class="dash-shell">
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <aside class="dash-sidebar" data-dash-sidebar>
        <a href="<?= $basePath ?>/dashboard" class="dash-sidebar-brand">
            <span class="seal">K</span>
            <span><span class="text-gradient">KADOSYS</span> Igrejas</span>
        </a>

        <nav class="dash-nav">
            <div class="dash-nav-group-label">Geral</div>
            <a href="<?= $basePath ?>/dashboard" class="dash-nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <div class="dash-nav-group-label">Modulos</div>
            <?php foreach ($modules as $slug => $module): ?>
                <?php $bloqueado = !Plano::disponivel($planoAtual, $slug); ?>
                <a href="<?= $basePath ?>/dashboard/<?= $slug ?>" class="dash-nav-link <?= $activeMenu === $slug ? 'active' : '' ?><?= $bloqueado ? ' dash-nav-link-locked' : '' ?>">
                    <i class="bi <?= htmlspecialchars($module['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    <?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($bloqueado): ?>
                        <i class="bi bi-lock-fill dash-nav-lock-icon" title="Disponivel no plano <?= htmlspecialchars(Plano::label($module['planoMinimo']), ENT_QUOTES, 'UTF-8') ?>"></i>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="dash-sidebar-footer">
            <div class="dash-ai-card">
                <div class="dash-ai-title"><i class="bi bi-stars"></i> Assistente IA</div>
                <p>Insights automaticos sobre membros, financas e engajamento chegam em breve.</p>
            </div>

            <form method="POST" action="<?= $basePath ?>/logout">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="dash-logout-btn">
                    <i class="bi bi-box-arrow-right"></i> Sair
                </button>
            </form>
        </div>
    </aside>

    <div class="dash-main">
        <header class="dash-topbar">
            <div class="dash-topbar-left">
                <button class="sidebar-toggle-btn" data-sidebar-open aria-label="Abrir menu">
                    <i class="bi bi-list"></i>
                </button>

                <div class="dash-breadcrumb">
                    <?php foreach ($breadcrumb as $index => $crumb): ?>
                        <?php if ($index > 0): ?><span class="sep">/</span><?php endif; ?>
                        <span class="<?= $index === count($breadcrumb) - 1 ? 'current' : '' ?>">
                            <?= htmlspecialchars($crumb, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dash-topbar-search">
                <i class="bi bi-search"></i>
                <input type="search" placeholder="Buscar membros, cultos, lancamentos..." aria-label="Buscar no sistema">
                <span class="kbd">/</span>
            </div>

            <div class="dash-topbar-right">
                <button class="topbar-icon-btn" type="button" aria-label="Notificacoes">
                    <i class="bi bi-bell"></i>
                    <span class="dot"></span>
                </button>

                <button class="topbar-icon-btn" data-theme-toggle aria-label="Alternar tema claro/escuro">
                    <i class="bi bi-sun" data-theme-icon></i>
                </button>

                <div class="topbar-user">
                    <span class="avatar"><?= htmlspecialchars($userInitial, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="name"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
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
