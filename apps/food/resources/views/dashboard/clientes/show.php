<?php

use Food\Models\Cliente;

/**
 * @var array $config
 * @var Cliente $cliente
 * @var array{totalPedidos: int, totalGasto: float, ticketMedio: float, ultimoPedidoEm: ?string} $estatisticas
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Vendas</p>
            <h1 class="dashboard-title"><?= htmlspecialchars($cliente->nome, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="dash-page-subtitle"><?= htmlspecialchars($cliente->whatsapp ?? $cliente->telefone ?? 'Sem contato cadastrado', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <a href="<?= $basePath ?>/dashboard/clientes/<?= $cliente->id ?>/editar" class="btn-k btn-k-outline">Editar</a>
            <a href="<?= $basePath ?>/dashboard/clientes" class="btn-k btn-k-outline">Voltar</a>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Histórico de compras</h2>
            <p class="dash-page-subtitle">Considera só pedidos confirmados (não conta pedidos ainda em montagem ou cancelados).</p>
        </div>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Total de pedidos</th>
                        <th>Total gasto</th>
                        <th>Ticket médio</th>
                        <th>Último pedido</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= $estatisticas['totalPedidos'] ?></td>
                        <td>R$ <?= number_format($estatisticas['totalGasto'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($estatisticas['ticketMedio'], 2, ',', '.') ?></td>
                        <td class="text-dim"><?= $estatisticas['ultimoPedidoEm'] !== null ? (new DateTimeImmutable($estatisticas['ultimoPedidoEm']))->format('d/m/Y') : '-' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Dados cadastrais</h2>
        </div>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Telefone</th>
                        <th>WhatsApp</th>
                        <th>Aniversário</th>
                        <th>Endereço</th>
                        <th>Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-dim"><?= htmlspecialchars($cliente->telefone ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-dim"><?= htmlspecialchars($cliente->whatsapp ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-dim"><?= $cliente->aniversario !== null ? (new DateTimeImmutable($cliente->aniversario))->format('d/m/Y') : '-' ?></td>
                        <td class="text-dim"><?= htmlspecialchars($cliente->endereco ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-dim"><?= htmlspecialchars($cliente->observacoes ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>
