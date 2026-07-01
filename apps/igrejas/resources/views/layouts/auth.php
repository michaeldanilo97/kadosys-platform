<?php
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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/[email protected]/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/[email protected]/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/auth.css">
</head>
<body>

<div class="auth-shell">
    <aside class="auth-aside">
        <a href="<?= $basePath ?>/" class="auth-aside-brand">
            <span class="seal">K</span>
            KADOSYS Igrejas
        </a>

        <div class="auth-aside-quote">
            <div class="folio-label">Folio de acesso</div>
            <h2>Cada registro da sua igreja, <em>guardado em um so lugar</em>.</h2>
        </div>

        <div class="auth-aside-foot">kadosys.com.br/apps/igrejas</div>
    </aside>

    <div class="auth-form-side">
        <?= $content ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/[email protected]/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
