<?php

use Food\Models\Restaurante;
use Food\Models\FaturaRestaurante;

/**
 * @var array $config
 * @var Restaurante $restaurante
 * @var array<int, FaturaRestaurante> $faturasPix
 * @var array<int, array>|null $cobrancasCartao
 * @var string $planoLabel
 * @var float $planoValor
 */
$basePath = $config['base_path'] ?? '';

$statusFaturaLabel = [
    FaturaRestaurante::STATUS_PENDENTE => ['Pendente', 'warn'],
    FaturaRestaurante::STATUS_PAGA => ['Paga', 'ok'],
    FaturaRestaurante::STATUS_EXPIRADA => ['Expirada', 'dim'],
    FaturaRestaurante::STATUS_CANCELADA => ['Cancelada', 'dim'],
];

$statusCartaoLabel = [
    'processed' => ['Processada', 'ok'],
    'pending' => ['Pendente', 'warn'],
    'rejected' => ['Recusada', 'danger'],
    'cancelled' => ['Cancelada', 'dim'],
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Cobrança</p>
            <h1 class="dashboard-title">Faturas</h1>
            <p class="dash-page-subtitle">Plano <?= htmlspecialchars($planoLabel, ENT_QUOTES, 'UTF-8') ?> · R$ <?= number_format($planoValor, 2, ',', '.') ?>/mês</p>
        </div>
        <a href="<?= $basePath ?>/dashboard/assinatura" class="btn-k btn-k-outline">Gerenciar pagamento</a>
    </div>

    <?php if ($restaurante->metodoPagamento === 'cartao'): ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Cobranças no cartão</h2>
            </div>

            <?php if ($cobrancasCartao === null): ?>
                <p class="crud-empty">Não foi possível buscar o extrato agora. Tente novamente mais tarde.</p>
            <?php elseif ($cobrancasCartao === []): ?>
                <p class="crud-empty">Nenhuma cobrança registrada ainda.</p>
            <?php else: ?>
                <div class="crud-table-wrapper">
                    <table class="crud-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cobrancasCartao as $cobranca): ?>
                                <?php
                                $statusCartao = (string) ($cobranca['status'] ?? '');
                                [$labelCartao, $badgeCartao] = $statusCartaoLabel[$statusCartao] ?? [$statusCartao !== '' ? $statusCartao : '-', 'dim'];
                                $dataCobranca = $cobranca['date_created'] ?? $cobranca['date_approved'] ?? null;
                                ?>
                                <tr>
                                    <td><?= $dataCobranca !== null ? (new DateTimeImmutable($dataCobranca))->format('d/m/Y') : '-' ?></td>
                                    <td>R$ <?= number_format((float) ($cobranca['transaction_amount'] ?? 0), 2, ',', '.') ?></td>
                                    <td><span class="status-badge <?= $badgeCartao ?>"><?= htmlspecialchars($labelCartao, ENT_QUOTES, 'UTF-8') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Cobranças via Pix</h2>
        </div>

        <?php if ($faturasPix === []): ?>
            <p class="crud-empty">Nenhuma cobrança Pix registrada ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Vencimento</th>
                            <th>Plano</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faturasPix as $fatura): ?>
                            <?php [$labelFatura, $badgeFatura] = $statusFaturaLabel[$fatura->status] ?? ['-', 'dim']; ?>
                            <tr>
                                <td><?= (new DateTimeImmutable($fatura->vencimento))->format('d/m/Y') ?></td>
                                <td class="text-dim"><?= htmlspecialchars(\Food\Models\Plano::label($fatura->plano), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>R$ <?= number_format($fatura->valor, 2, ',', '.') ?></td>
                                <td><span class="status-badge <?= $badgeFatura ?>"><?= $labelFatura ?></span></td>
                                <td>
                                    <?php if ($fatura->status === FaturaRestaurante::STATUS_PAGA && $fatura->pagoEm !== null): ?>
                                        <span class="text-dim">Pago em <?= (new DateTimeImmutable($fatura->pagoEm))->format('d/m/Y') ?></span>
                                    <?php elseif ($fatura->status === FaturaRestaurante::STATUS_PENDENTE): ?>
                                        <a href="<?= $basePath ?>/dashboard/assinatura" class="btn-k btn-k-grad btn-k-sm">Pagar agora</a>
                                    <?php else: ?>
                                        <span class="text-dim">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
