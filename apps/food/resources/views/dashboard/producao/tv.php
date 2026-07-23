<?php

use Food\Core\View;
use Food\Models\Pedido;
use Food\Models\PedidoItem;

/**
 * Variante fullscreen (sem sidebar) pensada pra ficar aberta numa TV da
 * cozinha - tema escuro fixo, fontes maiores, sem botao de avancar
 * status (so exibicao). O som de pedido novo exige um gesto do usuario
 * pra ser desbloqueado no navegador (ver assets/js/producao.js), por
 * isso o botao "Habilitar som" aparece so aqui.
 *
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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Produção (TV) - KADOSYS Food', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <script>document.documentElement.setAttribute('data-theme', 'dark');</script>
</head>
<body class="producao-tv-body">
<div class="producao-tv-shell">
    <header class="producao-tv-header">
        <h1><span class="text-gradient">KADOSYS</span> Food · Produção</h1>
        <div class="producao-tv-relogio" data-relogio><?= (new DateTimeImmutable())->format('H:i') ?></div>
        <button type="button" class="btn-habilitar-som" data-habilitar-som>
            <i class="bi bi-volume-up-fill"></i> Habilitar som
        </button>
    </header>

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
</div>

<script src="<?= $basePath ?>/assets/js/producao.js?v=<?= View::assetVersion('assets/js/producao.js') ?>"></script>
<script>
    setInterval(function () {
        var el = document.querySelector('[data-relogio]');
        if (!el) {
            return;
        }
        var agora = new Date();
        el.textContent = String(agora.getHours()).padStart(2, '0') + ':' + String(agora.getMinutes()).padStart(2, '0');
    }, 1000);
</script>
</body>
</html>
