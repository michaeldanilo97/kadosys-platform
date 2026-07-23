<?php

use Food\Core\Csrf;
use Food\Core\View;

/**
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 * @var \Food\Models\Restaurante|null $restaurante
 * @var \Food\Models\User|null $user
 * @var string|null $activeMenu
 */
$basePath = $config['base_path'] ?? '';
$menu = $activeMenu ?? '';
$tituloTopbar = preg_replace('/\s*-\s*KADOSYS Food$/', '', $pageTitle ?? '') ?: 'Painel';
$iniciais = $user?->name ? mb_strtoupper(mb_substr($user->name, 0, 1, 'UTF-8'), 'UTF-8') : 'U';

// Menu cresce fase a fase - os itens so entram aqui junto com o
// controller correspondente, pra nunca ter um link que ainda nao
// existe (Caixa/PDV, Producao, Financeiro, Precificacao, Relatorios e
// Configuracoes chegam nas proximas fases).
$itensMenu = [
    ['slug' => 'painel', 'href' => '/dashboard', 'icone' => 'bi-house-door-fill', 'label' => 'Painel'],
    ['slug' => 'pedidos', 'href' => '/dashboard/pedidos', 'icone' => 'bi-receipt-cutoff', 'label' => 'Pedidos'],
    ['slug' => 'produtos', 'href' => '/dashboard/produtos', 'icone' => 'bi-cake2-fill', 'label' => 'Produtos'],
    ['slug' => 'categorias', 'href' => '/dashboard/categorias', 'icone' => 'bi-tags-fill', 'label' => 'Categorias'],
    ['slug' => 'clientes', 'href' => '/dashboard/clientes', 'icone' => 'bi-people-fill', 'label' => 'Clientes'],
    ['slug' => 'ingredientes', 'href' => '/dashboard/ingredientes', 'icone' => 'bi-egg-fried', 'label' => 'Ingredientes'],
    ['slug' => 'estoque', 'href' => '/dashboard/estoque', 'icone' => 'bi-box-seam-fill', 'label' => 'Estoque'],
    ['slug' => 'compras', 'href' => '/dashboard/compras', 'icone' => 'bi-cart-check-fill', 'label' => 'Compras'],
    ['slug' => 'fornecedores', 'href' => '/dashboard/fornecedores', 'icone' => 'bi-truck', 'label' => 'Fornecedores'],
    ['slug' => 'faturas', 'href' => '/dashboard/faturas', 'icone' => 'bi-receipt', 'label' => 'Faturas'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'KADOSYS Food', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="manifest" href="<?= $basePath ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= $basePath ?>/assets/icons/apple-touch-icon.png">
    <meta name="theme-color" content="#0F172A">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/site.css?v=<?= View::assetVersion('assets/css/site.css') ?>">
    <?php if ($restaurante?->corPrimaria !== null): ?>
        <style>:root { --primary: <?= htmlspecialchars($restaurante->corPrimaria, ENT_QUOTES, 'UTF-8') ?>; }</style>
    <?php endif; ?>
    <script>
        // Aplica o tema ANTES da primeira renderizacao, pra nao piscar
        // escuro por uma fracao de segundo quando o padrao e o claro.
        // O tema padrao do painel e o claro - so troca se a pessoa ja
        // escolheu "escuro" antes (ver assets/js/dashboard.js).
        (function () {
            try {
                if (window.localStorage.getItem('kadosys_food_theme') !== 'dark') {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            } catch (error) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body data-base-path="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>">
<div class="dash-shell">
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <aside class="dash-sidebar" data-sidebar>
        <div class="dash-sidebar-brand">
            <?php if ($restaurante?->logoPath): ?>
                <img src="<?= $basePath . '/' . htmlspecialchars($restaurante->logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($restaurante->nome, ENT_QUOTES, 'UTF-8') ?>" class="dash-sidebar-logo">
            <?php else: ?>
                <span class="dash-sidebar-brand-label"><span class="text-gradient">KADOSYS</span> Food</span>
            <?php endif; ?>
            <button type="button" class="dash-sidebar-collapse-btn" data-sidebar-collapse aria-label="Recolher menu">
                <i class="bi bi-chevron-left"></i>
            </button>
        </div>
        <nav class="dash-nav">
            <?php foreach ($itensMenu as $item): ?>
                <a href="<?= $basePath . $item['href'] ?>" class="dash-nav-link<?= $menu === $item['slug'] ? ' active' : '' ?>" title="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="icone"><i class="bi <?= $item['icone'] ?>"></i></span>
                    <span class="dash-nav-link-label"><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="dash-sidebar-footer">
            <p class="restaurante-nome"><?= htmlspecialchars($restaurante->nome ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="usuario-nome"><?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="dash-sidebar-footer-actions">
                <button type="button" class="btn-theme-toggle" data-theme-toggle aria-label="Alternar tema claro/escuro">
                    <i class="bi bi-moon-stars" data-theme-icon></i> <span class="dash-sidebar-footer-label">Tema</span>
                </button>
            </div>
        </div>
    </aside>

    <div class="dash-main">
        <header class="dash-topbar">
            <button type="button" class="sidebar-toggle-btn" data-sidebar-toggle aria-label="Abrir menu"><i class="bi bi-list"></i></button>
            <h1 class="dash-topbar-title"><?= htmlspecialchars($tituloTopbar, ENT_QUOTES, 'UTF-8') ?></h1>

            <div class="topbar-dropdown dash-topbar-user" data-topbar-dropdown>
                <button type="button" class="dash-topbar-user-btn" aria-label="Menu do usuário" aria-expanded="false" data-dropdown-toggle>
                    <span class="dash-topbar-avatar"><?= htmlspecialchars($iniciais, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="dash-topbar-user-name"><?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="bi bi-chevron-down dash-topbar-user-chevron"></i>
                </button>

                <div class="topbar-dropdown-panel user-panel" data-dropdown-panel hidden>
                    <div class="topbar-dropdown-head"><?= htmlspecialchars($restaurante->nome ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <form method="POST" action="<?= $basePath ?>/logout" class="user-panel-item">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-logout-inline"><i class="bi bi-box-arrow-right"></i> Sair</button>
                    </form>
                </div>
            </div>
        </header>

        <?= $content ?>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/kadosys-modal.js?v=<?= View::assetVersion('assets/js/kadosys-modal.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/dashboard.js?v=<?= View::assetVersion('assets/js/dashboard.js') ?>"></script>
</body>
</html>
