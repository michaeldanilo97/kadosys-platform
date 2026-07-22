<?php

/**
 * @var array $config
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="cadastro-shell">
    <div class="glass-card pix-card">
        <div class="hero-eyebrow">Quase lá</div>
        <h1>Pagamento em processamento</h1>
        <p class="subtitle">Assim que o Mercado Pago confirmar, sua conta é liberada automaticamente. Isso costuma levar só alguns instantes.</p>
        <a href="<?= $basePath ?>/login" class="btn-k btn-k-grad" style="margin-top: 1rem;">Ir para o login</a>
    </div>
</div>
