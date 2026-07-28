<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var string $urlCheckin
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">QR de check-in</h1>
        <p class="dash-page-subtitle">
            Deixe esta tela num tablet ou imprima o QR na entrada. Qualquer membro escaneia com o próprio celular pra confirmar presença no culto do dia - sem precisar de login.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/cultos" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="dash-panel" style="text-align: center; max-width: 480px; margin: 0 auto;">
    <div data-checkin-qr style="display:flex; justify-content:center; margin: 1.2rem 0;"></div>
    <p class="auth-field-hint" style="word-break: break-all;"><?= htmlspecialchars($urlCheckin, ENT_QUOTES, 'UTF-8') ?></p>

    <form method="POST" action="<?= $basePath ?>/dashboard/cultos/checkin-qr/regenerar" style="margin-top: 1.4rem;" data-confirm="Gerar um QR novo? O QR impresso/exibido atualmente para de funcionar.">
        <?= $csrf ?>
        <button type="submit" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-repeat"></i> Gerar novo QR (invalida o atual)
        </button>
    </form>
</div>

<script>
    window.KADOSYS_CHECKIN_URL = <?= json_encode($urlCheckin) ?>;
</script>
<script src="<?= $basePath ?>/assets/js/vendor/qrcode-generator.js?v=<?= View::assetVersion('assets/js/vendor/qrcode-generator.js') ?>"></script>
<script>
    (function () {
        var alvo = document.querySelector('[data-checkin-qr]');
        if (!alvo || !window.qrcode || !window.KADOSYS_CHECKIN_URL) {
            return;
        }

        try {
            var qr = window.qrcode(0, 'M');
            qr.addData(window.KADOSYS_CHECKIN_URL);
            qr.make();
            alvo.innerHTML = qr.createImgTag(8, 8, 'QR code de check-in');
        } catch (erro) {
            alvo.innerHTML = '<p class="auth-field-hint">Não foi possível gerar o QR code.</p>';
        }
    })();
</script>
