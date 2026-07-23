<?php

use Food\Models\Pedido;

/**
 * @var array $config
 * @var array<int, Pedido> $pedidos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $statusSelecionado
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';

$labelOrigem = [
    Pedido::ORIGEM_BALCAO => 'Balcão',
    Pedido::ORIGEM_WHATSAPP => 'WhatsApp',
    Pedido::ORIGEM_IFOOD_MANUAL => 'iFood',
    Pedido::ORIGEM_DELIVERY_PROPRIO => 'Delivery próprio',
];

$labelStatus = [
    Pedido::STATUS_RECEBIDO => 'Recebido',
    Pedido::STATUS_EM_PREPARO => 'Em preparo',
    Pedido::STATUS_FINALIZADO => 'Finalizado',
    Pedido::STATUS_SAIU_PARA_ENTREGA => 'Saiu para entrega',
    Pedido::STATUS_ENTREGUE => 'Entregue',
    Pedido::STATUS_CANCELADO => 'Cancelado',
];

$badgeStatus = [
    Pedido::STATUS_RECEBIDO => 'dim',
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
            <h1 class="dashboard-title">Pedidos</h1>
            <p class="dash-page-subtitle"><?= $total ?> pedido<?= $total === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/pedidos/novo" class="btn-k btn-k-grad">+ Novo pedido</a>
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
        <form method="GET" action="<?= $basePath ?>/dashboard/pedidos" class="crud-search">
            <input type="text" name="busca" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome do cliente...">
            <select name="status">
                <option value="">Todos os status</option>
                <?php foreach ($labelStatus as $status => $label): ?>
                    <option value="<?= $status ?>" <?= $statusSelecionado === $status ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-k btn-k-outline">Buscar</button>
        </form>

        <?php if ($pedidos === []): ?>
            <p class="crud-empty">Nenhum pedido encontrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Origem</th>
                            <th>Valor total</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td class="text-dim"><?= (new DateTimeImmutable((string) $pedido->createdAt))->format('d/m/Y H:i') ?></td>
                                <td><?= htmlspecialchars($pedido->clienteNome ?? 'Sem cliente', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $labelOrigem[$pedido->origem] ?? $pedido->origem ?></td>
                                <td>R$ <?= number_format($pedido->valorTotal, 2, ',', '.') ?></td>
                                <td><span class="status-badge <?= $badgeStatus[$pedido->status] ?? 'dim' ?>"><?= $labelStatus[$pedido->status] ?? $pedido->status ?></span></td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/pedidos/<?= $pedido->id ?>" class="crud-icon-btn" title="Ver detalhes"><i class="bi bi-eye-fill"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($lastPage > 1): ?>
                <div class="crud-pagination">
                    <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                        <?php if ($p === $page): ?>
                            <span class="atual"><?= $p ?></span>
                        <?php else: ?>
                            <a href="<?= $basePath ?>/dashboard/pedidos?pagina=<?= $p ?>&busca=<?= urlencode($search) ?>&status=<?= urlencode((string) $statusSelecionado) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
