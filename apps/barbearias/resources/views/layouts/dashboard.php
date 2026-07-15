<?php

use Barbearias\Core\Csrf;
use Barbearias\Core\View;

/**
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 * @var \Barbearias\Models\Barbearia|null $barbearia
 * @var \Barbearias\Models\User|null $user
 * @var string|null $activeMenu
 */
$basePath = $config['base_path'] ?? '';
$menu = $activeMenu ?? '';

$itensMenu = [
    ['slug' => 'painel', 'href' => '/dashboard', 'icone' => '🏠', 'label' => 'Painel'],
    ['slug' => 'agendamentos', 'href' => '/dashboard/agendamentos', 'icone' => '📅', 'label' => 'Agendamentos'],
    ['slug' => 'bloqueios', 'href' => '/dashboard/bloqueios', 'icone' => '🚫', 'label' => 'Bloqueios'],
    ['slug' => 'lista-espera', 'href' => '/dashboard/lista-espera', 'icone' => '⏳', 'label' => 'Lista de espera'],
    ['slug' => 'clientes', 'href' => '/dashboard/clientes', 'icone' => '📇', 'label' => 'Clientes'],
    ['slug' => 'profissionais', 'href' => '/dashboard/profissionais', 'icone' => '👤', 'label' => 'Profissionais'],
    ['slug' => 'servicos', 'href' => '/dashboard/servicos', 'icone' => '✂️', 'label' => 'Serviços'],
    ['slug' => 'unidades', 'href' => '/dashboard/unidades', 'icone' => '🏢', 'label' => 'Unidades'],
    ['slug' => 'financeiro', 'href' => '/dashboard/financeiro', 'icone' => '💰', 'label' => 'Financeiro'],
    ['slug' => 'faturas', 'href' => '/dashboard/faturas', 'icone' => '🧾', 'label' => 'Faturas'],
    ['slug' => 'configuracoes', 'href' => '/dashboard/configuracoes', 'icone' => '⚙️', 'label' => 'Configurações'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'KADOSYS Barbearias', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/site.css?v=<?= View::assetVersion('assets/css/site.css') ?>">
    <script>
        // Aplica o tema ANTES da primeira renderizacao, pra nao piscar
        // escuro por uma fracao de segundo quando o padrao e o claro.
        // O tema padrao do painel e o claro - so troca se a pessoa ja
        // escolheu "escuro" antes (ver assets/js/dashboard.js).
        (function () {
            try {
                if (window.localStorage.getItem('kadosys_barbearias_theme') !== 'dark') {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            } catch (error) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body>
<div class="dash-shell">
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <aside class="dash-sidebar" data-sidebar>
        <div class="dash-sidebar-brand"><span class="text-gradient">KADOSYS</span> Barbearias</div>
        <nav class="dash-nav">
            <?php foreach ($itensMenu as $item): ?>
                <a href="<?= $basePath . $item['href'] ?>" class="dash-nav-link<?= $menu === $item['slug'] ? ' active' : '' ?>">
                    <span class="icone"><?= $item['icone'] ?></span>
                    <span><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="dash-sidebar-footer">
            <p class="barbearia-nome"><?= htmlspecialchars($barbearia->nome ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="usuario-nome"><?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="dash-sidebar-footer-actions">
                <button type="button" class="btn-theme-toggle" data-theme-toggle aria-label="Alternar tema claro/escuro">
                    <span data-theme-icon>🌙</span> Tema
                </button>
                <form method="POST" action="<?= $basePath ?>/logout">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn-logout">Sair</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="dash-main">
        <header class="dash-topbar">
            <button type="button" class="sidebar-toggle-btn" data-sidebar-toggle aria-label="Abrir menu">☰</button>
            <span class="brand"><span class="text-gradient">KADOSYS</span></span>
        </header>

        <?= $content ?>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/dashboard.js?v=<?= View::assetVersion('assets/js/dashboard.js') ?>"></script>
</body>
</html>
