<?php

use Food\Core\Csrf;
use Food\Models\Pedido;
use Food\Models\PedidoItem;

/**
 * Partial reaproveitado por index.php (dashboard normal) e tv.php
 * (tela cheia) - so muda o parametro $acoes (index mostra o botao de
 * avancar status, a TV so exibe).
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

$colunas = [
    ['titulo' => 'Recebido', 'pedidos' => $recebidos, 'proximoLabel' => 'Iniciar preparo'],
    ['titulo' => 'Em preparo', 'pedidos' => $emPreparo, 'proximoLabel' => 'Finalizar'],
    ['titulo' => 'Finalizado', 'pedidos' => $finalizados, 'proximoLabel' => 'Saiu para entrega'],
    ['titulo' => 'Saiu para entrega', 'pedidos' => $saiuParaEntrega, 'proximoLabel' => 'Marcar entregue'],
    ['titulo' => 'Entregue hoje', 'pedidos' => $entreguesHoje, 'proximoLabel' => null],
];

$idsConhecidos = array_merge(
    array_map(static fn (Pedido $p): int => $p->id, $recebidos),
    array_map(static fn (Pedido $p): int => $p->id, $emPreparo),
    array_map(static fn (Pedido $p): int => $p->id, $finalizados),
    array_map(static fn (Pedido $p): int => $p->id, $saiuParaEntrega),
);
?>
<div class="producao-board" data-quadro-producao data-dados-url="<?= $basePath ?>/dashboard/producao/dados" data-known-ids="<?= htmlspecialchars(implode(',', $idsConhecidos), ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($colunas as $coluna): ?>
        <div class="producao-coluna">
            <div class="producao-coluna-head">
                <h3><?= htmlspecialchars($coluna['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                <span class="producao-coluna-contagem"><?= count($coluna['pedidos']) ?></span>
            </div>

            <?php if ($coluna['pedidos'] === []): ?>
                <p class="producao-vazio">Nenhum pedido</p>
            <?php else: ?>
                <div class="producao-cards">
                    <?php foreach ($coluna['pedidos'] as $pedido): ?>
                        <?php $criadoEmMs = (new DateTimeImmutable($pedido->createdAt))->getTimestamp() * 1000; ?>
                        <div class="producao-card" data-pedido-card data-created-ms="<?= $criadoEmMs ?>">
                            <div class="producao-card-head">
                                <strong>#<?= $pedido->id ?></strong>
                                <span class="producao-timer" data-timer>00:00</span>
                            </div>
                            <p class="producao-card-cliente"><?= htmlspecialchars($pedido->clienteNome ?? 'Sem cliente', ENT_QUOTES, 'UTF-8') ?></p>
                            <ul class="producao-card-itens">
                                <?php foreach (($itensPorPedido[$pedido->id] ?? []) as $item): ?>
                                    <li><?= $item->quantidade ?>x <?= htmlspecialchars($item->produtoNome, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($acoes && $coluna['proximoLabel'] !== null): ?>
                                <form method="POST" action="<?= $basePath ?>/dashboard/producao/<?= $pedido->id ?>/avancar">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn-k btn-k-grad" style="width: 100%;"><?= htmlspecialchars($coluna['proximoLabel'], ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
