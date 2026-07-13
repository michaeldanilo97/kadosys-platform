<?php

use Igrejas\Core\View;
use Igrejas\Models\Plano;

/**
 * @var array $config
 * @var \Igrejas\Models\FaturaPix $fatura
 */
$basePath = $config['base_path'] ?? '';
$vencimento = new DateTimeImmutable($fatura->vencimento);
?>

<h1 class="dash-page-title">Confirme o upgrade</h1>
<p class="dash-page-subtitle">Seu acesso continua normal enquanto isso - isso é só o complemento pra liberar o plano maior antes do fim do ciclo atual.</p>

<div class="placeholder-box placeholder-box-plano" style="max-width: 480px; margin: 0 auto;">
    <div class="icon"><i class="bi bi-qr-code"></i></div>

    <h2>Pague com Pix pra liberar o plano <?= htmlspecialchars(Plano::label($fatura->plano), ENT_QUOTES, 'UTF-8') ?></h2>

    <div class="pix-valor">R$ <?= number_format($fatura->valor, 2, ',', '.') ?></div>
    <p class="auth-field-hint" style="margin-bottom: 1rem;">Valor proporcional aos dias restantes do seu ciclo atual - não é o valor cheio do plano.</p>

    <?php if ($fatura->pixQrCodeBase64): ?>
        <div class="pix-qr-wrap">
            <img src="data:image/png;base64,<?= htmlspecialchars($fatura->pixQrCodeBase64, ENT_QUOTES, 'UTF-8') ?>" alt="QR code Pix">
        </div>
    <?php endif; ?>

    <div class="auth-field" style="text-align: left;">
        <label for="pix_copia_cola">Pix copia e cola</label>
        <div class="auth-slug-input">
            <input type="text" class="form-control" id="pix_copia_cola" value="<?= htmlspecialchars((string) $fatura->pixQrCode, ENT_QUOTES, 'UTF-8') ?>" readonly>
            <button type="button" class="pix-copiar-btn" data-pix-copiar aria-label="Copiar código Pix">
                <i class="bi bi-clipboard"></i>
            </button>
        </div>
    </div>

    <span class="auth-field-hint">Vence em <?= $vencimento->format('d/m/Y \à\s H:i') ?> - se expirar, é só voltar em Configurações e clicar em upgrade de novo.</span>

    <div class="pix-status" data-pix-status>
        <i class="bi bi-hourglass-split"></i> Aguardando o pagamento...
    </div>
</div>

<script>
  window.KADOSYS_PIX_STATUS_URL = <?= json_encode($basePath . '/dashboard/configuracoes/upgrade-pendente/status') ?>;
  window.KADOSYS_PIX_RETORNO_URL = <?= json_encode($basePath . '/dashboard/configuracoes') ?>;
</script>
<script src="<?= $basePath ?>/assets/js/dashboard-fatura-pix.js?v=<?= View::assetVersion('assets/js/dashboard-fatura-pix.js') ?>"></script>
