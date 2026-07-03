<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var \Igrejas\Models\FaturaPix|null $fatura
 */
$basePath = $config['base_path'] ?? '';
$valor = $fatura?->valor ?? 0.0;
$vencimento = $fatura?->vencimento ? new DateTimeImmutable($fatura->vencimento) : null;
// A mesma tela cobre dois cenarios: uma fatura recem-gerada (troca de
// plano via Pix ou renovacao em dia, ainda dentro do prazo) e uma fatura
// realmente vencida sem pagamento (que bloqueou o acesso - ver
// AuthMiddleware). O texto muda conforme o caso pra nao dizer "venceu"
// de algo que ainda nem chegou no vencimento.
$faturaVencida = $vencimento !== null && $vencimento < new DateTimeImmutable();
?>

<h1 class="dash-page-title"><?= $faturaVencida ? 'Fatura pendente' : 'Confirme o pagamento' ?></h1>
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
        <?php if ($faturaVencida): ?>
            <h2>Sua fatura de <?= htmlspecialchars(\Igrejas\Models\Plano::label($fatura->plano), ENT_QUOTES, 'UTF-8') ?> venceu</h2>
        <?php else: ?>
            <h2>Pague com Pix pra confirmar o plano <?= htmlspecialchars(\Igrejas\Models\Plano::label($fatura->plano), ENT_QUOTES, 'UTF-8') ?></h2>
        <?php endif; ?>

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
