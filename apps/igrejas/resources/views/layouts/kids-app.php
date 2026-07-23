<?php

use Igrejas\Core\View;

/**
 * Layout minimo do "modo criança" (ver KidsLoginController/
 * KidsAppController) - sem sidebar/topbar administrativo de proposito,
 * so o mundo colorido da Biblioteca Kids (ver kids-biblioteca.css),
 * pra crianca usar sozinha num tablet/celular.
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
    <title><?= htmlspecialchars($pageTitle ?? 'KADOSYS Kids', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/kids-biblioteca.css?v=<?= View::assetVersion('assets/css/kids-biblioteca.css') ?>">
</head>
<body class="kids-standalone">
<?= $content ?>
<script src="<?= $basePath ?>/assets/js/kids-sons.js?v=<?= View::assetVersion('assets/js/kids-sons.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-interacoes.js?v=<?= View::assetVersion('assets/js/kids-interacoes.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-jogo-memoria.js?v=<?= View::assetVersion('assets/js/kids-jogo-memoria.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-jogo-trivia.js?v=<?= View::assetVersion('assets/js/kids-jogo-trivia.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-jogo-cacapalavras.js?v=<?= View::assetVersion('assets/js/kids-jogo-cacapalavras.js') ?>"></script>
</body>
</html>
