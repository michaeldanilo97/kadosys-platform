<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var \Igrejas\Models\FaturaPix|null $fatura
 */
$basePath = $config['base_path'] ?? '';
$valor = $fatura?->valor ?? 0.0;
$vencimento = $fatura?->vencimento ? new DateTimeImmutable($fatura->vencimento) : null;
?>

<h1 class="dash-page-title">Fatura pendente</h1>
<p class="dash-page-subtitle">O acesso da igreja fica liberado de novo assim que o Pix for confirmado.</p>

<div class="placeholder-box placeholder-box-plano" style="max-width: 480px; margin: 0 auto;">
    <div class="icon"><i class="bi bi-qr-code"></i></div>

    <?php if ($fatura === null || $fatura->pixQrCode === null): ?>
        <h2>Nao foi possivel gerar a cobranca agora</h2>
        <p>
            Tivemos um problema ao gerar uma nova cobranca Pix. Tente atualizar esta pagina em alguns minutos
            ou fale com o suporte.
        </p>
    <?php else: ?>
        <h2>Sua fatura de <?= htmlspecialchars(\Igrejas\Models\Plano::label($fatura->plano), ENT_QUOTES, 'UTF-8') ?> venceu</h2>

        <div class="pix-valor">R$ <?= number_format($valor, 2, ',', '.') ?></div>

        <?php if ($fatura->pixQrCodeBase64): ?>
            <div class="pix-qr-wrap">
                <img src="data:image/png;base64,<?= htmlspecialchars($fatura->pixQrCodeBase64, ENT_QUOTES, 'UTF-8') ?>" alt="QR code Pix">
            </div>
        <?php endif; ?>

        <div class="auth-field" style="text-align: left;">
            <label for="pix_copia_cola">Pix copia e cola</label>
            <div class="auth-slug-input">
                <input type="text" class="form-control" id="pix_copia_cola" value="<?= htmlspecialchars($fatura->pixQrCode, ENT_QUOTES, 'UTF-8') ?>" readonly>
                <button type="button" class="pix-copiar-btn" data-pix-copiar aria-label="Copiar codigo Pix">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
        </div>

        <?php if ($vencimento): ?>
            <span class="auth-field-hint">Vence em <?= $vencimento->format('d/m/Y \à\s H:i') ?>.</span>
        <?php endif; ?>

        <div class="pix-status" data-pix-status>
            <i class="bi bi-hourglass-split"></i> Aguardando o pagamento...
        </div>
    <?php endif; ?>
</div>

<script>
  window.KADOSYS_PIX_STATUS_URL = <?= json_encode($basePath . '/dashboard/fatura-vencida/status') ?>;
  window.KADOSYS_PIX_RETORNO_URL = <?= json_encode($basePath . '/dashboard') ?>;
</script>
<script src="<?= $basePath ?>/assets/js/dashboard-fatura-pix.js?v=<?= View::assetVersion('assets/js/dashboard-fatura-pix.js') ?>"></script>
