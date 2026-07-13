<?php

use Igrejas\Core\View;

/**
 * Layout do Modo Culto - tela dedicada sem sidebar/menu do painel,
 * pensada pra ficar aberta no celular/tablet de cada musico durante o
 * culto: so a musica atual (cifra/tom/andamento) e o chat rapido entre
 * musicos. Mesma familia do layout "tela-cheia" (ver
 * dashboard/louvores/tela-cheia.php), mas com seu proprio CSS/JS
 * dedicado (ver repertorio-culto.css/.js).
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
