<?php

use Food\Core\Csrf;
use Food\Models\Pedido;
use Food\Models\PedidoItem;
use Food\Models\Produto;

/**
 * @var array $config
 * @var Pedido $pedido
 * @var array<int, PedidoItem> $itens
 * @var array<int, Produto> $produtos
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$baseUrl = $basePath . '/dashboard/pedidos/' . $pedido->id;
$editavel = $pedido->status === Pedido::STATUS_MONTAGEM;

$labelOrigem = [
    Pedido::ORIGEM_BALCAO => 'Balcão',
    Pedido::ORIGEM_WHATSAPP => 'WhatsApp',
    Pedido::ORIGEM_IFOOD_MANUAL => 'iFood (registro manual)',
    Pedido::ORIGEM_DELIVERY_PROPRIO => 'Delivery próprio',
];

$labelStatus = [
    Pedido::STATUS_MONTAGEM => 'Montagem',
    Pedido::STATUS_RECEBIDO => 'Recebido',
    Pedido::STATUS_EM_PREPARO => 'Em preparo',
    Pedido::STATUS_FINALIZADO => 'Finalizado',
    Pedido::STATUS_SAIU_PARA_ENTREGA => 'Saiu para entrega',
    Pedido::STATUS_ENTREGUE => 'Entregue',
    Pedido::STATUS_CANCELADO => 'Cancelado',
];

$badgeStatus = [
    Pedido::STATUS_MONTAGEM => 'dim',
    Pedido::STATUS_RECEBIDO => 'ok',
    Pedido::STATUS_EM_PREPARO => 'ok',
    Pedido::STATUS_FINALIZADO => 'ok',
    Pedido::STATUS_SAIU_PARA_ENTREGA => 'ok',
    Pedido::STATUS_ENTREGUE => 'ok',
    Pedido::STATUS_CANCELADO => 'danger',
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Vendas</p>
            <h1 class="dashboard-title">Pedido #<?= $pedido->id ?></h1>
            <p class="dash-page-subtitle">
                <?= htmlspecialchars($pedido->clienteNome ?? 'Sem cliente identificado', ENT_QUOTES, 'UTF-8') ?> ·
                <?= $labelOrigem[$pedido->origem] ?? $pedido->origem ?> ·
                <span class="status-badge <?= $badgeStatus[$pedido->status] ?? 'dim' ?>"><?= $labelStatus[$pedido->status] ?? $pedido->status ?></span>
            </p>
        </div>
        <a href="<?= $basePath ?>/dashboard/pedidos" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Resumo</h2>
        </div>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Subtotal</th>
                        <th>Desconto</th>
                        <th>Valor total</th>
                        <th>Pagamento</th>
                        <th>Endereço de entrega</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>R$ <?= number_format($pedido->subtotal, 2, ',', '.') ?></td>
                        <td class="text-dim">R$ <?= number_format($pedido->desconto, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($pedido->valorTotal, 2, ',', '.') ?></td>
                        <td class="text-dim"><?= htmlspecialchars($pedido->formaPagamento, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-dim"><?= htmlspecialchars($pedido->enderecoEntrega ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if ($editavel): ?>
            <div class="crud-form-actions">
                <form method="POST" action="<?= $baseUrl ?>/confirmar" data-confirm="Confirmar este pedido? O estoque dos ingredientes será baixado e não poderá ser desfeito.">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn-k btn-k-grad">Confirmar pedido</button>
                </form>
                <form method="POST" action="<?= $baseUrl ?>/cancelar" data-confirm="Cancelar este pedido?">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn-k btn-k-outline">Cancelar pedido</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Itens do pedido</h2>
        </div>

        <?php if ($editavel): ?>
            <?php if ($produtos === []): ?>
                <p class="crud-empty">Cadastre produtos ativos antes de montar o pedido.</p>
            <?php else: ?>
                <form method="POST" action="<?= $baseUrl ?>/itens" class="crud-form-grid" style="margin-bottom: 1.5rem;">
                    <?= Csrf::field() ?>
                    <div class="form-field">
                        <label for="produto_id">Produto</label>
                        <select id="produto_id" name="produto_id" required>
                            <?php foreach ($produtos as $produto): ?>
                                <option value="<?= $produto->id ?>"><?= htmlspecialchars($produto->nome, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="quantidade">Quantidade</label>
                        <input type="number" id="quantidade" name="quantidade" min="1" value="1" required>
                    </div>
                    <div class="form-field">
                        <label for="preco_unitario">Preço unitário</label>
                        <input type="text" id="preco_unitario" name="preco_unitario" placeholder="0,00" required>
                    </div>
                    <div class="form-field">
                        <label for="observacao">Observação/adicionais</label>
                        <input type="text" id="observacao" name="observacao" placeholder="Sem cebola, + bacon...">
                    </div>
                    <div class="form-field" style="align-self: flex-end;">
                        <button type="submit" class="btn-k btn-k-grad">+ Adicionar item</button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($itens === []): ?>
            <p class="crud-empty">Nenhum item neste pedido ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Preço unitário</th>
                            <th>Subtotal</th>
                            <th>Observação</th>
                            <?php if ($editavel): ?>
                                <th></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item->produtoNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $item->quantidade ?></td>
                                <td class="text-dim">R$ <?= number_format($item->precoUnitario, 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($item->subtotal, 2, ',', '.') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($item->observacao ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <?php if ($editavel): ?>
                                    <td class="actions-col">
                                        <form method="POST" action="<?= $baseUrl ?>/itens/<?= $item->id ?>/excluir" data-confirm="Remover este item do pedido?">
                                            <?= Csrf::field() ?>
                                            <button type="submit" class="crud-icon-btn danger" title="Remover"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
