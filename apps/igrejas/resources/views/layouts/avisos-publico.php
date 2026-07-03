<?php

declare(strict_types=1);

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
    <title><?= htmlspecialchars($pageTitle ?? 'Avisos - KADOSYS Igrejas', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/avisos-publico.css?v=<?= View::assetVersion('assets/css/avisos-publico.css') ?>">
</head>
<body class="avisos-publico-body">
<?= $content ?>
</body>
</html>
