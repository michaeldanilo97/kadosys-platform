<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var string|null $nomeIgreja
 * @var \Igrejas\Models\FinanceiroDoacao $doacao
 * @var string $pixPayload
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$confirmada = $doacao->status === 'confirmada';
?>
<div class="auth-form-card">
    <div class="eyebrow"><?= htmlspecialchars($nomeIgreja ?? 'Nossa igreja', ENT_QUOTES, 'UTF-8') ?></div>
    <h1><?= $confirmada ? 'Obrigado pela doacao!' : 'Pague com Pix' ?></h1>

    <?php if ($confirmada): ?>
        <div class="auth-alert" style="background: rgba(52,211,153,0.12); border-color: rgba(52,211,153,0.35); color: var(--success);">
            <i class="bi bi-check-circle"></i> Recebemos sua confirmacao. Que Deus abencoe sua generosidade!
        </div>
    <?php else: ?>
        <p class="subtitle">Escaneie o QR code no app do seu banco, ou use o codigo "copia e cola". Depois de pagar, confirme aqui embaixo.</p>
    <?php endif; ?>

    <div class="pix-valor">R$ <?= number_format($doacao->valor, 2, ',', '.') ?></div>

    <?php if (!$confirmada): ?>
        <div class="pix-qr-wrap" data-pix-qr></div>

        <div class="auth-field">
            <label for="pix_copia_cola">Pix copia e cola</label>
            <div class="auth-slug-input">
                <input type="text" class="form-control" id="pix_copia_cola" value="<?= htmlspecialchars($pixPayload, ENT_QUOTES, 'UTF-8') ?>" readonly>
                <button type="button" class="pix-copiar-btn" data-pix-copiar aria-label="Copiar codigo Pix">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
        </div>

        <form method="POST" action="<?= $basePath ?>/doar/<?= $doacao->id ?>/confirmar">
            <?= $csrf ?>
            <button type="submit" class="btn-k btn-k-grad">Ja fiz o Pix <i class="bi bi-check2"></i></button>
        </form>

        <p class="auth-field-hint" style="margin-top: 0.8rem; text-align: center;">
            O Pix cai direto na conta da igreja - o sistema nao recebe confirmacao automatica do banco, por isso pedimos que voce confirme aqui.
        </p>
    <?php endif; ?>

    <a href="<?= $basePath ?>/doar" class="auth-back-link">
        <i class="bi bi-arrow-left"></i> Fazer outra doacao
    </a>
</div>

<?php if (!$confirmada): ?>
    <script>
      window.KADOSYS_PIX_PAYLOAD = <?= json_encode($pixPayload) ?>;
    </script>
    <script src="<?= $basePath ?>/assets/js/vendor/qrcode-generator.js?v=<?= View::assetVersion('assets/js/vendor/qrcode-generator.js') ?>"></script>
    <script src="<?= $basePath ?>/assets/js/doacao-pix.js?v=<?= View::assetVersion('assets/js/doacao-pix.js') ?>"></script>
<?php endif; ?>
