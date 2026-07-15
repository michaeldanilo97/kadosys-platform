<?php

use Barbearias\Models\Barbearia;
use Barbearias\Models\FaturaBarbearia;
use Barbearias\Models\Plano;

/**
 * @var array $config
 * @var array<int, string> $errors
 * @var Barbearia $barbearia
 * @var FaturaBarbearia|null $fatura
 */
$basePath = $config['base_path'] ?? '';
$trialVencido = $barbearia->metodoPagamento === 'trial'
    && $barbearia->trialExpiraEm !== null
    && new DateTimeImmutable() > new DateTimeImmutable($barbearia->trialExpiraEm);
?>
<main class="dashboard-main">
    <p class="dashboard-eyebrow">Assinatura</p>
    <h1 class="dashboard-title"><?= $trialVencido ? 'Seu teste grátis terminou' : 'Pagamento pendente' ?></h1>
    <p class="subtitle">
        <?= $trialVencido
            ? 'Assine o plano ' . htmlspecialchars(Plano::label($barbearia->plano), ENT_QUOTES, 'UTF-8') . ' pra continuar usando o KADOSYS Barbearias. Seus dados continuam guardados com segurança.'
            : 'Confirme o pagamento do plano ' . htmlspecialchars(Plano::label($barbearia->plano), ENT_QUOTES, 'UTF-8') . ' pra liberar o acesso de novo.' ?>
    </p>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card cadastro-card" style="margin-top: 1.5rem;">
        <?php if ($fatura !== null): ?>
            <div class="hero-eyebrow">Pague com Pix</div>
            <h2>Escaneie o QR Code</h2>
            <p class="subtitle">Assim que o pagamento cair, sua conta é liberada automaticamente.</p>

            <?php if ($fatura->pixQrCodeBase64): ?>
                <img src="data:image/png;base64,<?= htmlspecialchars($fatura->pixQrCodeBase64, ENT_QUOTES, 'UTF-8') ?>" alt="QR Code Pix">
            <?php endif; ?>

            <div class="pix-copiacola">
                <input type="text" value="<?= htmlspecialchars((string) $fatura->pixQrCode, ENT_QUOTES, 'UTF-8') ?>" readonly data-pix-copia-cola>
                <button type="button" class="btn-k btn-k-grad btn-k-sm" data-pix-copiar>Copiar</button>
            </div>

            <p class="pix-status" data-pix-status>Aguardando confirmação do pagamento…</p>

            <form method="POST" action="<?= $basePath ?>/dashboard/assinatura/pix" style="margin-top: 1rem;">
                <?= \Barbearias\Core\Csrf::field() ?>
                <button type="submit" class="btn-k btn-k-outline">Gerar novo QR Code</button>
            </form>
        <?php else: ?>
            <div class="escolha-grid">
                <form method="POST" action="<?= $basePath ?>/dashboard/assinatura/cartao">
                    <?= \Barbearias\Core\Csrf::field() ?>
                    <button type="submit" class="escolha-card" style="width:100%; cursor:pointer; background:none; border-width:2px;">
                        <span class="nome">💳 Cartão</span>
                        <span class="desc">Cobrança automática todo mês</span>
                    </button>
                </form>
                <form method="POST" action="<?= $basePath ?>/dashboard/assinatura/pix">
                    <?= \Barbearias\Core\Csrf::field() ?>
                    <button type="submit" class="escolha-card" style="width:100%; cursor:pointer; background:none; border-width:2px;">
                        <span class="nome">🔳 Pix</span>
                        <span class="desc">Fatura nova todo mês, paga na hora</span>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php if ($fatura !== null): ?>
<script>
    (function () {
        var faturaId = <?= (int) $fatura->id ?>;
        var statusEl = document.querySelector('[data-pix-status]');
        var copiarBtn = document.querySelector('[data-pix-copiar]');
        var copiaColaInput = document.querySelector('[data-pix-copia-cola]');
        var basePath = <?= json_encode($basePath, JSON_UNESCAPED_SLASHES) ?>;

        if (copiarBtn && copiaColaInput) {
            copiarBtn.addEventListener('click', function () {
                copiaColaInput.select();
                navigator.clipboard.writeText(copiaColaInput.value).then(function () {
                    copiarBtn.textContent = 'Copiado!';
                    setTimeout(function () { copiarBtn.textContent = 'Copiar'; }, 2000);
                });
            });
        }

        function verificar() {
            fetch(basePath + '/dashboard/assinatura/status')
                .then(function (resposta) { return resposta.json(); })
                .then(function (dados) {
                    if (dados.status === 'paga') {
                        statusEl.textContent = 'Pagamento confirmado! Redirecionando…';
                        window.location.href = basePath + '/dashboard';

                        return;
                    }

                    setTimeout(verificar, 5000);
                })
                .catch(function () {
                    setTimeout(verificar, 8000);
                });
        }

        setTimeout(verificar, 5000);
    })();
</script>
<?php endif; ?>
