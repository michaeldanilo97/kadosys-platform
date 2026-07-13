<?php
/**
 * @var array $module
 */
?>

<h1 class="dash-page-title"><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?></h1>
<p class="dash-page-subtitle"><?= htmlspecialchars($module['description'], ENT_QUOTES, 'UTF-8') ?></p>

<div class="placeholder-box">
    <div class="icon"><i class="bi <?= htmlspecialchars($module['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
    <h2><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?></h2>
    <p>
        A estrutura desta página já está pronta. As funcionalidades do módulo
        <?= htmlspecialchars(mb_strtolower($module['title']), ENT_QUOTES, 'UTF-8') ?>
        serão implementadas em uma próxima sprint de desenvolvimento.
    </p>
    <span class="status-tag">Estrutura pronta &middot; em desenvolvimento</span>
</div>
