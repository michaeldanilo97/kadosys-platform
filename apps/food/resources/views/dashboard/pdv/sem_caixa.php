<?php

/**
 * @var array $config
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Venda</p>
            <h1 class="dashboard-title">PDV</h1>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Nenhum caixa aberto</h2>
        </div>
        <p>Abra o caixa antes de iniciar uma venda pelo PDV.</p>
        <a href="<?= $basePath ?>/dashboard/caixa" class="btn-k btn-k-grad">Ir para o Caixa</a>
    </div>
</main>
