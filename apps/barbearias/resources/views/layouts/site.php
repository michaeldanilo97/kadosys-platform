<?php

use Barbearias\Core\View;

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
    <title><?= htmlspecialchars($pageTitle ?? 'KADOSYS Barbearias', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="Gestão completa para barbearias: agendamento, profissionais, serviços e clientes num só lugar.">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/site.css?v=<?= View::assetVersion('assets/css/site.css') ?>">
</head>
<body>
<header class="site-nav">
    <div class="site-nav-inner">
        <span class="site-brand"><span class="text-gradient">KADOSYS</span> Barbearias</span>
        <nav class="site-nav-links">
            <a href="<?= $basePath ?>/#planos">Planos</a>
            <a href="<?= $basePath ?>/login">Entrar</a>
            <a href="<?= $basePath ?>/cadastro" class="btn-k btn-k-grad btn-k-sm">Testar grátis</a>
        </nav>
    </div>
</header>

<?= $content ?>

<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> KADOSYS<sup class="brand-tm">TM</sup> Tecnologia. Todos os direitos reservados.</p>
</footer>
</body>
</html>
