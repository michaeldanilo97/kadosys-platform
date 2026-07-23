<?php

use Academias\Core\Csrf;
use Academias\Core\View;
use Academias\Models\Academia;

/**
 * @var array $config
 * @var Academia $academia
 * @var string $urlCheckin
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Check-in</p>
            <h1 class="dashboard-title">QR Code da entrada</h1>
            <p class="dash-page-subtitle">Deixe esta tela num tablet ou imprima o QR na recepção. O aluno escaneia pra entrar e escaneia de novo pra sair.</p>
        </div>
        <a href="<?= $basePath ?>/dashboard/checkin" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <div class="glass-card dash-panel" style="text-align: center; max-width: 480px; margin: 0 auto;">
        <div data-checkin-qr style="display:flex; justify-content:center; margin: 1.5rem 0;"></div>
        <p class="form-field-hint" style="word-break: break-all;"><?= htmlspecialchars($urlCheckin, ENT_QUOTES, 'UTF-8') ?></p>

        <form method="POST" action="<?= $basePath ?>/dashboard/checkin/qr/regenerar" style="margin-top: 1.5rem;" data-confirm="Gerar um QR novo? O QR impresso/exibido atualmente para de funcionar.">
            <?= Csrf::field() ?>
            <button type="submit" class="btn-k btn-k-outline">Gerar novo QR (invalida o atual)</button>
        </form>
    </div>
</main>

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
            alvo.innerHTML = '<p class="form-field-hint">Não foi possível gerar o QR code.</p>';
        }
    })();
</script>
