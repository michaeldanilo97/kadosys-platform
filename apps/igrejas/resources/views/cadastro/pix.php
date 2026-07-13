<?php

use Igrejas\Core\View;
use Igrejas\Models\Plano;

/**
 * @var array $config
 * @var \Igrejas\Models\Provisionamento $provisionamento
 */
$basePath = $config['base_path'] ?? '';
$valor = Plano::VALOR_MENSAL[$provisionamento->plano] ?? 0.0;
$vencimento = $provisionamento->pixVencimento ? new DateTimeImmutable($provisionamento->pixVencimento) : null;
?>
<div class="auth-form-card">
    <div class="eyebrow">Falta pouco</div>
    <h1>Pague com Pix pra ativar</h1>
    <p class="subtitle">
        Escaneie o QR code no app do seu banco, ou use o código "copia e cola".
        Assim que o pagamento for confirmado, sua igreja é criada automaticamente.
    </p>

    <div class="pix-valor">R$ <?= number_format($valor, 2, ',', '.') ?></div>

    <?php if ($provisionamento->pixQrCodeBase64): ?>
        <div class="pix-qr-wrap">
            <img src="data:image/png;base64,<?= htmlspecialchars($provisionamento->pixQrCodeBase64, ENT_QUOTES, 'UTF-8') ?>" alt="QR code Pix">
        </div>
    <?php endif; ?>

    <div class="auth-field">
        <label for="pix_copia_cola">Pix copia e cola</label>
        <div class="auth-slug-input">
            <input type="text" class="form-control" id="pix_copia_cola" value="<?= htmlspecialchars($provisionamento->pixQrCode ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly>
            <button type="button" class="pix-copiar-btn" data-pix-copiar aria-label="Copiar código Pix">
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
</div>

<script>
  window.KADOSYS_PIX_STATUS_URL = <?= json_encode($basePath . '/cadastro/pix/' . $provisionamento->id . '/status') ?>;
  window.KADOSYS_PIX_RETORNO_URL = <?= json_encode($basePath . '/cadastro/retorno') ?>;
</script>
<script src="<?= $basePath ?>/assets/js/cadastro-pix.js?v=<?= View::assetVersion('assets/js/cadastro-pix.js') ?>"></script>
