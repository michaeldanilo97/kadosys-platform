<?php

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
    <title><?= htmlspecialchars($pageTitle ?? 'KADOSYS Igrejas', ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/auth.css?v=<?= View::assetVersion('assets/css/auth.css') ?>">
</head>
<body>

<div class="bg-aurora" aria-hidden="true"></div>
<div class="bg-grid" aria-hidden="true"></div>

<div class="auth-shell">
    <aside class="auth-aside">
        <a href="<?= $basePath ?>/" class="auth-aside-brand brand-mark">
            <span class="seal">K</span>
            <span class="text-gradient">KADOSYS</span>&nbsp;Igrejas
        </a>

        <div class="auth-aside-quote">
            <span class="eyebrow">Plataforma inteligente</span>
            <h2>A gestão da sua igreja, <span class="text-gradient">conectada ao futuro</span>.</h2>
            <div class="auth-aside-points">
                <div><i class="bi bi-shield-check"></i> Dados exclusivos da sua igreja</div>
                <div><i class="bi bi-cloud-check"></i> Acesso de qualquer lugar</div>
                <div><i class="bi bi-stars"></i> Insights com IA em breve</div>
            </div>
        </div>

        <div class="auth-aside-foot">kadosys.com.br/apps/igrejas</div>
    </aside>

    <div class="auth-form-side">
        <?= $content ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
