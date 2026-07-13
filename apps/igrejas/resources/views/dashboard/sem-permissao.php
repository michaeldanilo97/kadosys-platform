<?php

/**
 * @var array $config
 * @var array $module
 */
$basePath = $config['base_path'] ?? '';
?>

<h1 class="dash-page-title"><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?></h1>
<p class="dash-page-subtitle">Você não tem permissão para acessar este módulo.</p>

<div class="placeholder-box placeholder-box-plano">
    <div class="icon"><i class="bi bi-shield-lock"></i></div>
    <h2>Acesso restrito a <?= htmlspecialchars(mb_strtolower($module['title']), ENT_QUOTES, 'UTF-8') ?></h2>
    <p>
        Sua conta não tem permissão para acessar este módulo. Se você acredita que deveria ter acesso,
        fale com um administrador da igreja para liberar em Permissões.
    </p>
    <a href="<?= $basePath ?>/dashboard" class="btn-k btn-k-grad" style="margin-top: 1.2rem;">
        <i class="bi bi-arrow-left"></i> Voltar para o Dashboard
    </a>
</div>
