<?php

use Barbearias\Core\Csrf;
use Barbearias\Core\View;

/**
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 * @var \Barbearias\Models\Barbearia|null $barbearia
 */
$basePath = $config['base_path'] ?? '';
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
<header class="dashboard-topbar">
    <span class="brand"><span class="text-gradient">KADOSYS</span> Barbearias<?= $barbearia !== null ? ' · ' . htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') : '' ?></span>
    <form method="POST" action="<?= $basePath ?>/logout">
        <?= Csrf::field() ?>
        <button type="submit" class="btn-logout">Sair</button>
    </form>
</header>

<?= $content ?>
</body>
</html>
