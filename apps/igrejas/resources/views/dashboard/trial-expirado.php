<?php

/**
 * @var array $config
 * @var \Igrejas\Models\Tenant $tenant
 */
$basePath = $config['base_path'] ?? '';
$expirouEm = $tenant->trialExpiraEm !== null ? new DateTimeImmutable($tenant->trialExpiraEm) : null;
?>

<h1 class="dash-page-title">Seu teste gratis terminou</h1>
<p class="dash-page-subtitle">Escolha um plano pra continuar usando o KADOSYS Igrejas.</p>

<div class="placeholder-box placeholder-box-plano">
    <div class="icon"><i class="bi bi-hourglass-bottom"></i></div>
    <h2>Os 7 dias de teste gratis acabaram<?= $expirouEm !== null ? ' em ' . $expirouEm->format('d/m/Y') : '' ?></h2>
    <p>
        Seus dados continuam guardados com seguranca - assine um plano (cartao ou Pix) pra liberar o acesso de
        novo, sem perder nada do que ja foi cadastrado.
    </p>
    <a href="<?= $basePath ?>/dashboard/configuracoes#plano-contratado" class="btn-k btn-k-grad" style="margin-top: 1.2rem;">
        <i class="bi bi-arrow-up-circle"></i> Escolher um plano
    </a>
</div>
