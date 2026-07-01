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
        A estrutura desta pagina ja esta pronta. As funcionalidades do modulo
        <?= htmlspecialchars(mb_strtolower($module['title']), ENT_QUOTES, 'UTF-8') ?>
        serao implementadas em uma proxima sprint de desenvolvimento.
    </p>
    <span class="status-tag">Estrutura pronta &middot; em desenvolvimento</span>
</div>
