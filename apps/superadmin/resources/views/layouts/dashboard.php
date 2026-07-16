<?php

use Superadmin\Core\Csrf;
use Superadmin\Core\View;

/**
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 * @var string|null $activeMenu
 */
$basePath = $config['base_path'] ?? '';
$menu = $activeMenu ?? '';

$itensMenu = [
    ['slug' => 'sites', 'href' => '/sites', 'label' => 'Sites'],
    ['slug' => 'avisos', 'href' => '/avisos', 'label' => 'Avisos'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'KADOSYS Super Admin', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="sidebar-brand"><span class="text-gradient">KADOSYS</span> Super Admin</div>
        <nav style="display:flex; flex-direction:column; gap:4px;">
            <?php foreach ($itensMenu as $item): ?>
                <a href="<?= $basePath . $item['href'] ?>" class="nav-link<?= $menu === $item['slug'] ? ' active' : '' ?>">
                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <form method="POST" action="<?= $basePath ?>/sair">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-sm" style="width:100%;">Sair</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <?= $content ?>
    </div>
</div>
</body>
</html>
