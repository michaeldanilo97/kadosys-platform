<?php

use Food\Core\View;
use Food\Models\Pedido;
use Food\Models\PedidoItem;

/**
 * @var array $config
 * @var array<int, Pedido> $recebidos
 * @var array<int, Pedido> $emPreparo
 * @var array<int, Pedido> $finalizados
 * @var array<int, Pedido> $saiuParaEntrega
 * @var array<int, Pedido> $entreguesHoje
 * @var array<int, array<int, PedidoItem>> $itensPorPedido
 * @var bool $acoes
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Cozinha</p>
            <h1 class="dashboard-title">Produção</h1>
            <p class="dash-page-subtitle">Pedidos confirmados, do recebido até a entrega</p>
        </div>
        <a href="<?= $basePath ?>/dashboard/producao/tv" class="btn-k btn-k-outline" target="_blank" rel="noopener">
            <i class="bi bi-tv"></i> Abrir modo TV
        </a>
    </div>

    <?= View::render('dashboard.producao._board', [
        'config' => $config,
        'recebidos' => $recebidos,
        'emPreparo' => $emPreparo,
        'finalizados' => $finalizados,
        'saiuParaEntrega' => $saiuParaEntrega,
        'entreguesHoje' => $entreguesHoje,
        'itensPorPedido' => $itensPorPedido,
        'acoes' => $acoes,
    ]) ?>
</main>

<script src="<?= $basePath ?>/assets/js/producao.js?v=<?= View::assetVersion('assets/js/producao.js') ?>"></script>
