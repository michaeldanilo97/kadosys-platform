<?php

use Igrejas\Models\Plano;

/**
 * Painel "Plano contratado" - extraido de configuracoes/index.php pra
 * poder ser reaproveitado tambem pela tela dedicada de quem tem acesso
 * ao modulo Financeiro mas nao e admin (ver
 * FinanceiroController::plano()), ja que Configuracoes como um todo
 * continua sendo so pra admin (ver User::MODULOS_SOMENTE_ADMIN).
 *
 * @var \Igrejas\Models\ConfiguracaoIgreja $configuracao
 * @var bool $emTrial
 * @var \Igrejas\Models\Tenant|null $tenant
 * @var \Igrejas\Models\Assinatura|null $assinatura
 * @var bool $pagamentoConfigurado
 * @var bool $pixDisponivel
 * @var string $csrf
 * @var string $basePath
 * @var bool $permiteAlterarPlano Quem so tem acesso ao Financeiro (nao admin)
 *      pode ver o plano contratado, mas nao alterar/assinar - essa decisao
 *      fica so com o admin (ver FinanceiroController::plano()).
 */
$permiteAlterarPlano ??= true;
$valorPorPlano = Plano::VALOR_MENSAL;

// Upgrade/downgrade proporcional ao ciclo ja pago - so se aplica a quem
// ja tem um plano pago em andamento (nao trial, nem primeira
// assinatura, que nao tem ciclo anterior pra aproveitar). Ver
// Igrejas\Controllers\AssinaturaController::iniciar() pra regra
// completa - aqui e so a previa mostrada na tela.
$diasRestantesCiclo = null;

if (!$emTrial && $tenant !== null && $tenant->proximoVencimento !== null) {
    $vencimentoCiclo = new DateTimeImmutable($tenant->proximoVencimento);
    $hoje = new DateTimeImmutable('today');
    $diasRestantesCiclo = $hoje < $vencimentoCiclo ? $hoje->diff($vencimentoCiclo)->days : 0;
}

/** @var array<string, string> */
$statusAssinaturaLabel = [
    'pendente' => 'Pagamento pendente',
    'autorizada' => 'Assinatura ativa',
    'pausada' => 'Assinatura pausada',
    'cancelada' => 'Assinatura cancelada',
];
?>
<div class="dash-panel" id="plano-contratado">
    <div class="dash-panel-head">
        <h2><i class="bi bi-star"></i> Plano contratado</h2>
    </div>
    <?php if ($emTrial): ?>
        <p class="dash-page-subtitle" style="margin-bottom: 0.6rem;">
            Você está no teste grátis, com todos os módulos liberados<?php if ($tenant->trialExpiraEm !== null): ?> até <strong><?= (new DateTimeImmutable($tenant->trialExpiraEm))->format('d/m/Y') ?></strong><?php endif; ?>.
            Escolha um plano abaixo para continuar usando o sistema depois do teste.
        </p>
    <?php else: ?>
        <p class="dash-page-subtitle" style="margin-bottom: 0.6rem;">
            Define quais módulos ficam disponíveis no menu lateral. Plano atual:
            <strong><?= htmlspecialchars(Plano::label($configuracao->plano), ENT_QUOTES, 'UTF-8') ?></strong>.
            <?php if ($assinatura !== null): ?>
                <span class="plano-status-badge plano-status-<?= htmlspecialchars($assinatura->status, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($statusAssinaturaLabel[$assinatura->status] ?? $assinatura->status, ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if (!$permiteAlterarPlano): ?>
        <div class="crud-alert" style="background: rgba(93, 156, 236, 0.1); border-color: rgba(93, 156, 236, 0.35); color: #5d9cec;">
            <i class="bi bi-info-circle"></i>
            Somente administradores podem alterar ou assinar um plano. Fale com o administrador da igreja se precisar mudar.
        </div>
    <?php elseif (!$pagamentoConfigurado): ?>
        <div class="crud-alert" style="background: rgba(212, 161, 63, 0.1); border-color: rgba(212, 161, 63, 0.35); color: #d4a13f;">
            <i class="bi bi-info-circle"></i>
            Pagamento online ainda não foi configurado neste servidor. Fale com o suporte para habilitar a assinatura automática.
        </div>
    <?php endif; ?>

    <div class="plano-assinar-grid">
        <?php foreach ($valorPorPlano as $valor => $preco): ?>
            <?php
                $ehPlanoAtual = !$emTrial && $configuracao->plano === $valor;
                $ehDowngrade = $diasRestantesCiclo !== null && $diasRestantesCiclo > 0 && $preco < ($valorPorPlano[$tenant->plano] ?? $preco);
                $ehUpgrade = $diasRestantesCiclo !== null && $diasRestantesCiclo > 0 && $preco > ($valorPorPlano[$tenant->plano] ?? $preco);
                $jaAgendadoPraEsse = $tenant !== null && $tenant->planoAgendado === $valor;
                $valorProporcional = $ehUpgrade
                    ? round(($preco - $valorPorPlano[$tenant->plano]) * $diasRestantesCiclo / 30, 2)
                    : null;
            ?>
            <div class="plano-assinar-card<?= $ehPlanoAtual ? ' atual' : '' ?>">
                <strong><?= htmlspecialchars(Plano::label($valor), ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="preco">R$ <?= number_format($preco, 2, ',', '.') ?><small>/mês</small></span>
                <?php if ($ehPlanoAtual): ?>
                    <span class="plano-assinar-atual-tag">Plano atual</span>
                <?php elseif (!$permiteAlterarPlano): ?>
                <?php elseif ($jaAgendadoPraEsse): ?>
                    <span class="plano-assinar-agendado-tag">
                        <i class="bi bi-clock-history"></i> Agendado para <?= (new DateTimeImmutable($tenant->proximoVencimento))->format('d/m/Y') ?>
                    </span>
                <?php elseif ($ehDowngrade): ?>
                    <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/assinatura/<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>" data-confirm="O plano só muda no fim do ciclo atual (<?= (new DateTimeImmutable($tenant->proximoVencimento))->format('d/m/Y') ?>), sem reembolso do que já foi pago. Confirma?">
                        <?= $csrf ?>
                        <button type="submit" class="btn-k btn-k-ghost">
                            <i class="bi bi-arrow-down-circle"></i> Agendar downgrade
                        </button>
                        <span class="plano-assinar-hint">Passa a valer em <?= (new DateTimeImmutable($tenant->proximoVencimento))->format('d/m/Y') ?>, sem cobrar nada agora.</span>
                    </form>
                <?php elseif ($ehUpgrade): ?>
                    <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/assinatura/<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>">
                        <?= $csrf ?>
                        <button type="submit" class="btn-k btn-k-grad" <?= $pagamentoConfigurado ? '' : 'disabled' ?>>
                            <i class="bi bi-qr-code"></i> Upgrade agora - R$ <?= number_format($valorProporcional, 2, ',', '.') ?>
                        </button>
                        <span class="plano-assinar-hint">Valor proporcional aos dias restantes do seu ciclo atual, via Pix. O valor cheio passa a valer só no próximo ciclo.</span>
                    </form>
                <?php else: ?>
                    <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/assinatura/<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>">
                        <?= $csrf ?>
                        <?php if ($pixDisponivel): ?>
                            <div class="plano-assinar-metodo">
                                <label>
                                    <input type="radio" name="metodo_pagamento" value="cartao" checked>
                                    Cartão
                                </label>
                                <label>
                                    <input type="radio" name="metodo_pagamento" value="pix">
                                    Pix
                                </label>
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="btn-k btn-k-grad" <?= $pagamentoConfigurado ? '' : 'disabled' ?>>
                            <i class="bi bi-credit-card"></i> Assinar
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
