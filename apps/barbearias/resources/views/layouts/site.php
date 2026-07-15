<?php

use Barbearias\Core\View;
use Barbearias\Models\Barbearia;

/**
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 * @var Barbearia|null $barbearia Presente so nas paginas de uma barbearia especifica (agendamento publico) - ausente na landing/cadastro/login.
 */
$basePath = $config['base_path'] ?? '';
$marca = $barbearia ?? null;
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
    <?php if ($marca?->corPrimaria !== null): ?>
        <style>:root { --primary: <?= htmlspecialchars($marca->corPrimaria, ENT_QUOTES, 'UTF-8') ?>; }</style>
    <?php endif; ?>
</head>
<body>
<header class="site-nav">
    <div class="site-nav-inner">
        <?php if ($marca?->logoPath): ?>
            <span class="site-brand"><img src="<?= $basePath . '/' . htmlspecialchars($marca->logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($marca->nome, ENT_QUOTES, 'UTF-8') ?>" class="site-brand-logo"></span>
        <?php elseif ($marca !== null): ?>
            <span class="site-brand"><?= htmlspecialchars($marca->nome, ENT_QUOTES, 'UTF-8') ?></span>
        <?php else: ?>
            <span class="site-brand"><span class="text-gradient">KADOSYS</span> Barbearias</span>
        <?php endif; ?>
        <?php if ($marca === null): ?>
            <nav class="site-nav-links">
                <a href="<?= $basePath ?>/#planos">Planos</a>
                <a href="<?= $basePath ?>/login">Entrar</a>
                <a href="<?= $basePath ?>/cadastro" class="btn-k btn-k-grad btn-k-sm">Testar grátis</a>
            </nav>
        <?php endif; ?>
    </div>
</header>

<?= $content ?>

<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> KADOSYS<sup class="brand-tm">TM</sup> Tecnologia. Todos os direitos reservados.</p>
</footer>
</body>
</html>
