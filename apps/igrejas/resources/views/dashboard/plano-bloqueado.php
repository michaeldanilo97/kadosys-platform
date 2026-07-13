<?php

use Igrejas\Models\Plano;

/**
 * @var array $config
 * @var array $module
 * @var string $planoAtual
 */
$basePath = $config['base_path'] ?? '';
$planoNecessario = Plano::label($module['planoMinimo']);
?>

<h1 class="dash-page-title"><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?></h1>
<p class="dash-page-subtitle">Este módulo faz parte de um plano superior ao contratado atualmente.</p>

<div class="placeholder-box placeholder-box-plano">
    <div class="icon"><i class="bi bi-lock-fill"></i></div>
    <h2><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?> é um recurso do plano <?= htmlspecialchars($planoNecessario, ENT_QUOTES, 'UTF-8') ?></h2>
    <p>
        Sua igreja está no plano <strong><?= htmlspecialchars(Plano::label($planoAtual), ENT_QUOTES, 'UTF-8') ?></strong>.
        Para liberar <?= htmlspecialchars(mb_strtolower($module['title']), ENT_QUOTES, 'UTF-8') ?> e os demais recursos
        do plano <?= htmlspecialchars($planoNecessario, ENT_QUOTES, 'UTF-8') ?>, faça o upgrade em Configurações.
    </p>
    <a href="<?= $basePath ?>/dashboard/configuracoes#plano-contratado" class="btn-k btn-k-grad" style="margin-top: 1.2rem;">
        <i class="bi bi-arrow-up-circle"></i> Ver planos em Configurações
    </a>
</div>
