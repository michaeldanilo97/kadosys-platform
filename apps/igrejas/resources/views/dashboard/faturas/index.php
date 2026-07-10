<?php

use Igrejas\Models\FaturaPix;
use Igrejas\Models\Plano;

/**
 * @var array $config
 * @var \Igrejas\Models\Tenant|null $tenant
 * @var \Igrejas\Models\FaturaPix[] $faturas
 * @var int|null $faturaPendenteId
 * @var \Igrejas\Models\AssinaturaTenant|null $assinaturaCartao
 */
$basePath = $config['base_path'] ?? '';

$statusLabels = [
    'pendente' => 'Pendente',
    'paga' => 'Paga',
    'expirada' => 'Vencida',
    'cancelada' => 'Cancelada',
];

$tipoLabels = [
    FaturaPix::TIPO_RENOVACAO => 'Renovacao mensal',
    FaturaPix::TIPO_UPGRADE_PROPORCIONAL => 'Upgrade de plano',
];

$assinaturaStatusLabels = [
    'pendente' => 'Pagamento pendente',
    'autorizada' => 'Assinatura ativa',
    'pausada' => 'Assinatura pausada',
    'cancelada' => 'Assinatura cancelada',
];

$emTrial = $tenant !== null && $tenant->metodoPagamento === 'trial';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Faturas</h1>
        <p class="dash-page-subtitle">Historico de cobrancas, vencimento e pagamento do plano contratado.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/configuracoes#plano-contratado" class="btn-k btn-k-ghost">
            <i class="bi bi-star"></i> Ver planos
        </a>
    </div>
</div>

<?php if ($tenant === null): ?>
    <div class="dash-panel">
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-receipt-cutoff"></i></div>
            <h2>Historico de faturas indisponivel</h2>
            <p>Esse recurso e exclusivo de contas hospedadas automaticamente (com subdominio proprio).</p>
        </div>
    </div>
<?php else: ?>

    <?php if ($emTrial): ?>
        <div class="dash-panel">
            <p class="dash-page-subtitle" style="margin: 0;">
                <i class="bi bi-gift"></i>
                Voce esta no teste gratis<?php if ($tenant->trialExpiraEm !== null): ?> ate <strong><?= (new DateTimeImmutable($tenant->trialExpiraEm))->format('d/m/Y') ?></strong><?php endif; ?> -
                nenhuma cobranca foi feita ainda.
                <a href="<?= $basePath ?>/dashboard/configuracoes#plano-contratado">Escolha um plano</a> quando quiser continuar depois do teste.
            </p>
        </div>
    <?php elseif ($tenant->metodoPagamento === 'cartao'): ?>
        <div class="dash-panel">
            <div class="dash-panel-head">
                <h2><i class="bi bi-credit-card"></i> Assinatura por cartao</h2>
            </div>
            <p class="dash-page-subtitle" style="margin-bottom: 0;">
                Plano <strong><?= htmlspecialchars(Plano::label($tenant->plano), ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if ($assinaturaCartao !== null): ?>
                    <span class="plano-status-badge plano-status-<?= htmlspecialchars($assinaturaCartao->status, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($assinaturaStatusLabels[$assinaturaCartao->status] ?? $assinaturaCartao->status, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
                <br>
                Cobranca recorrente automatica direto no Mercado Pago - o historico detalhado de cada cobranca fica
                la, disponivel na propria conta do Mercado Pago usada no pagamento.
                <?php if ($tenant->proximoVencimento !== null): ?>
                    Proxima renovacao prevista: <strong><?= (new DateTimeImmutable($tenant->proximoVencimento))->format('d/m/Y') ?></strong>.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-receipt-cutoff"></i> Cobrancas via Pix</h2>
        </div>

        <?php if ($faturas === []): ?>
            <div class="crud-empty">
                <div class="icon"><i class="bi bi-receipt"></i></div>
                <h2>Nenhuma cobranca via Pix ainda</h2>
                <p>
                    <?= $tenant->metodoPagamento === 'cartao'
                        ? 'Quem paga por cartao so tem fatura Pix aqui em upgrades pontuais de plano.'
                        : 'Assim que a primeira cobranca for gerada, ela aparece nesta lista.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Vencimento</th>
                            <th>Plano</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th class="actions-col">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faturas as $fatura): ?>
                            <tr>
                                <td><?= (new DateTimeImmutable($fatura->vencimento))->format('d/m/Y') ?></td>
                                <td><?= htmlspecialchars(Plano::label($fatura->plano), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($tipoLabels[$fatura->tipo] ?? $fatura->tipo, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>R$ <?= number_format($fatura->valor, 2, ',', '.') ?></td>
                                <td>
                                    <span class="plano-status-badge plano-status-<?= $fatura->status === 'paga' ? 'autorizada' : ($fatura->status === 'expirada' ? 'cancelada' : $fatura->status) ?>">
                                        <?= htmlspecialchars($statusLabels[$fatura->status] ?? $fatura->status, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="actions-col">
                                    <?php if ($fatura->id === $faturaPendenteId): ?>
                                        <a href="<?= $basePath ?>/dashboard/fatura-vencida" class="btn-k btn-k-grad">
                                            <i class="bi bi-qr-code"></i> Pagar agora
                                        </a>
                                    <?php elseif ($fatura->status === 'paga' && $fatura->pagoEm !== null): ?>
                                        <span class="crud-text-dim">Pago em <?= (new DateTimeImmutable($fatura->pagoEm))->format('d/m/Y') ?></span>
                                    <?php else: ?>
                                        <span class="crud-text-dim">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
