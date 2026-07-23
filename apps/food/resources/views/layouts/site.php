<?php

use Food\Core\View;
use Food\Models\Restaurante;

/**
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 * @var Restaurante|null $restaurante Presente so em paginas de um restaurante especifico (uso futuro) - ausente na landing/cadastro/login.
 */
$basePath = $config['base_path'] ?? '';
$marca = $restaurante ?? null;
$siteInstitucional = $marca === null;
?>
<!DOCTYPE html>
<html lang="pt-BR"<?= $siteInstitucional ? ' class="js"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'KADOSYS Food', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="Gestão completa para confeitarias, restaurantes e delivery: ficha técnica, estoque, PDV, pedidos e financeiro num só lugar.">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/site.css?v=<?= View::assetVersion('assets/css/site.css') ?>">
    <?php if ($marca?->corPrimaria !== null): ?>
        <style>:root { --primary: <?= htmlspecialchars($marca->corPrimaria, ENT_QUOTES, 'UTF-8') ?>; }</style>
    <?php endif; ?>
</head>
<body>
<?php if ($siteInstitucional): ?>
    <div class="bg-aurora"></div>
    <div class="bg-grid"></div>
<?php endif; ?>

<header class="site-nav" data-site-nav>
    <div class="site-nav-inner">
        <?php if ($marca?->logoPath): ?>
            <span class="site-brand"><img src="<?= $basePath . '/' . htmlspecialchars($marca->logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($marca->nome, ENT_QUOTES, 'UTF-8') ?>" class="site-brand-logo"></span>
        <?php elseif ($marca !== null): ?>
            <span class="site-brand"><?= htmlspecialchars($marca->nome, ENT_QUOTES, 'UTF-8') ?></span>
        <?php else: ?>
            <span class="site-brand"><span class="text-gradient">KADOSYS</span> Food</span>
        <?php endif; ?>
        <?php if ($siteInstitucional): ?>
            <button type="button" class="site-nav-toggle" data-nav-toggle aria-label="Abrir menu" aria-expanded="false">
                <i class="bi bi-list"></i>
            </button>
            <nav class="site-nav-links" data-nav-links>
                <a href="<?= $basePath ?>/#recursos">Recursos</a>
                <a href="<?= $basePath ?>/#planos">Planos</a>
                <a href="<?= $basePath ?>/#faq">Dúvidas</a>
                <a href="<?= $basePath ?>/login">Entrar</a>
                <a href="<?= $basePath ?>/cadastro" class="btn-k btn-k-grad btn-k-sm">Testar grátis</a>
            </nav>
        <?php endif; ?>
    </div>
</header>

<?= $content ?>

<footer class="site-footer">
    <?php if ($siteInstitucional): ?>
        <div class="site-footer-inner">
            <div class="site-footer-col site-footer-brand">
                <span class="site-brand"><span class="text-gradient">KADOSYS</span> Food</span>
                <p>Gestão completa pra confeitaria, restaurante ou delivery: ficha técnica, estoque, PDV, pedidos e financeiro num só painel.</p>
            </div>
            <div class="site-footer-col">
                <h4>Produto</h4>
                <a href="<?= $basePath ?>/#recursos">Recursos</a>
                <a href="<?= $basePath ?>/#planos">Planos</a>
                <a href="<?= $basePath ?>/#faq">Perguntas frequentes</a>
            </div>
            <div class="site-footer-col">
                <h4>Comece agora</h4>
                <a href="<?= $basePath ?>/cadastro">Criar conta grátis</a>
                <a href="<?= $basePath ?>/login">Entrar no painel</a>
            </div>
        </div>
        <div class="site-footer-bottom">
            <p>&copy; <?= date('Y') ?> KADOSYS<sup class="brand-tm">TM</sup> Tecnologia. Todos os direitos reservados.</p>
        </div>
    <?php else: ?>
        <p>&copy; <?= date('Y') ?> KADOSYS<sup class="brand-tm">TM</sup> Tecnologia. Todos os direitos reservados.</p>
    <?php endif; ?>
</footer>

<?php if ($siteInstitucional): ?>
    <script src="<?= $basePath ?>/assets/js/landing.js?v=<?= View::assetVersion('assets/js/landing.js') ?>"></script>
<?php endif; ?>
</body>
</html>
