<?php

use Igrejas\Core\View;

/**
 * Layout minimalista pra telas "sem distração" abertas numa segunda tela
 * (tablet do músico, monitor no palco) - sem sidebar/topbar do painel,
 * so o conteudo. Ver dashboard/louvores/tela-cheia.php pro primeiro uso.
 *
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
</head>
<body>
<?= $content ?>
</body>
</html>
