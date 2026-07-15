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
    ['slug' => 'clientes', 'href' => '/dashboard/clientes', 'icone' => '📇', 'label' => 'Clientes'],
    ['slug' => 'profissionais', 'href' => '/dashboard/profissionais', 'icone' => '👤', 'label' => 'Profissionais'],
    ['slug' => 'servicos', 'href' => '/dashboard/servicos', 'icone' => '✂️', 'label' => 'Serviços'],
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
            <form method="POST" action="<?= $basePath ?>/logout">
                <?= Csrf::field() ?>
                <button type="submit" class="btn-logout" style="width:100%;">Sair</button>
            </form>
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

<script>
    (function () {
        var sidebar = document.querySelector('[data-sidebar]');
        var overlay = document.querySelector('[data-sidebar-overlay]');
        var toggleBtn = document.querySelector('[data-sidebar-toggle]');

        function fechar() {
            sidebar.classList.remove('aberta');
            overlay.classList.remove('aberta');
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                sidebar.classList.toggle('aberta');
                overlay.classList.toggle('aberta');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', fechar);
        }
    })();
</script>
</body>
</html>
