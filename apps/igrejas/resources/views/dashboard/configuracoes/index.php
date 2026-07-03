<?php

use Igrejas\Models\Plano;

/**
 * @var array $config
 * @var \Igrejas\Models\ConfiguracaoIgreja $configuracao
 * @var \Igrejas\Models\Assinatura|null $assinatura
 * @var bool $pagamentoConfigurado
 * @var bool $pixDisponivel
 * @var \Igrejas\Models\Tenant|null $tenant
 * @var string|null $success
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$logoUrl = $configuracao->logoPath ? $basePath . '/' . $configuracao->logoPath : null;

$valorPorPlano = Plano::VALOR_MENSAL;

// Durante o teste gratis nenhum plano foi de fato contratado ainda -
// mesmo que a igreja tenha "escolhido" um plano no cadastro, nenhum
// deles pode aparecer como "atual" (o botao Assinar precisa continuar
// disponivel pros 3, inclusive pro que ela esta testando agora).
$emTrial = $tenant !== null && $tenant->metodoPagamento === 'trial';

/** @var array<string, string> */
$statusAssinaturaLabel = [
    'pendente' => 'Pagamento pendente',
    'autorizada' => 'Assinatura ativa',
    'pausada' => 'Assinatura pausada',
    'cancelada' => 'Assinatura cancelada',
];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Configuracoes</h1>
        <p class="dash-page-subtitle">Dados gerais da igreja e preferencias do sistema.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="crud-alert error">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="dash-panel" id="plano-contratado">
    <div class="dash-panel-head">
        <h2><i class="bi bi-star"></i> Plano contratado</h2>
    </div>
    <?php if ($emTrial): ?>
        <p class="dash-page-subtitle" style="margin-bottom: 0.6rem;">
            Voce esta no teste gratis, com todos os modulos liberados<?php if ($tenant->trialExpiraEm !== null): ?> ate <strong><?= (new DateTimeImmutable($tenant->trialExpiraEm))->format('d/m/Y') ?></strong><?php endif; ?>.
            Escolha um plano abaixo para continuar usando o sistema depois do teste.
        </p>
    <?php else: ?>
        <p class="dash-page-subtitle" style="margin-bottom: 0.6rem;">
            Define quais modulos ficam disponiveis no menu lateral. Plano atual:
            <strong><?= htmlspecialchars(Plano::label($configuracao->plano), ENT_QUOTES, 'UTF-8') ?></strong>.
            <?php if ($assinatura !== null): ?>
                <span class="plano-status-badge plano-status-<?= htmlspecialchars($assinatura->status, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($statusAssinaturaLabel[$assinatura->status] ?? $assinatura->status, ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if (!$pagamentoConfigurado): ?>
        <div class="crud-alert" style="background: rgba(212, 161, 63, 0.1); border-color: rgba(212, 161, 63, 0.35); color: #d4a13f;">
            <i class="bi bi-info-circle"></i>
            Pagamento online ainda nao foi configurado neste servidor. Fale com o suporte para habilitar a assinatura automatica.
        </div>
    <?php endif; ?>

    <div class="plano-assinar-grid">
        <?php foreach ($valorPorPlano as $valor => $preco): ?>
            <?php $ehPlanoAtual = !$emTrial && $configuracao->plano === $valor; ?>
            <div class="plano-assinar-card<?= $ehPlanoAtual ? ' atual' : '' ?>">
                <strong><?= htmlspecialchars(Plano::label($valor), ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="preco">R$ <?= number_format($preco, 2, ',', '.') ?><small>/mes</small></span>
                <?php if ($ehPlanoAtual): ?>
                    <span class="plano-assinar-atual-tag">Plano atual</span>
                <?php else: ?>
                    <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/assinatura/<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>">
                        <?= $csrf ?>
                        <?php if ($pixDisponivel): ?>
                            <div class="plano-assinar-metodo">
                                <label>
                                    <input type="radio" name="metodo_pagamento" value="cartao" checked>
                                    Cartao
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

<div class="dash-panel">
    <div class="dash-panel-head">
        <h2><i class="bi bi-image"></i> Logo da igreja</h2>
    </div>
    <p class="dash-page-subtitle" style="margin-bottom: 1.4rem;">
        Usada na tela de projecao quando o operador aplica o "fadeout" de um video.
    </p>

    <?php if ($logoUrl): ?>
        <div class="logo-preview">
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo atual da igreja">
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/logo" enctype="multipart/form-data" class="crud-form">
        <?= $csrf ?>
        <div class="crud-field">
            <label for="logo">Enviar nova logo (PNG, JPG, WEBP ou SVG, ate 5MB)</label>
            <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" required>
        </div>
        <div class="crud-form-actions" style="justify-content: flex-start;">
            <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-upload"></i> Salvar logo</button>
        </div>
    </form>

    <?php if ($logoUrl): ?>
        <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/logo/remover" data-confirm="Remover a logo atual?" style="margin-top: 0.6rem;">
            <?= $csrf ?>
            <button type="submit" class="btn-k btn-k-outline" style="border-color: rgba(248,113,113,0.4); color: var(--danger);">
                <i class="bi bi-trash"></i> Remover logo
            </button>
        </form>
    <?php endif; ?>
</div>
