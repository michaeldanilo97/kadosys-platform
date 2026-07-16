<?php

use Barbearias\Core\Csrf;
use Barbearias\Core\View;
use Barbearias\Models\Agendamento;
use Barbearias\Models\AssinaturaCliente;

/**
 * @var array $config
 * @var Agendamento $agendamento
 * @var array<int, string> $formasPagamento
 * @var array{assinatura: AssinaturaCliente, usados: int, restantes: int}|null $saldoAssinatura
 * @var string|null $pixPayload
 * @var array<int, string> $errors
 * @var array $old
 */
$basePath = $config['base_path'] ?? '';

$labelFormaPagamento = [
    'dinheiro' => 'Dinheiro',
    'pix' => 'Pix',
    'cartao_credito' => 'Cartão de crédito',
    'cartao_debito' => 'Cartão de débito',
    'outro' => 'Outro',
];

$valorSugerido = $old['valor'] ?? number_format($agendamento->servicoPreco, 2, '.', '');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Agenda</p>
            <h1 class="dashboard-title">Concluir e registrar pagamento</h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/agendamentos" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card cadastro-card">
        <div class="confirmacao-detalhes" style="margin-bottom: 1.5rem;">
            <div><span>Cliente</span><span><?= htmlspecialchars($agendamento->clienteNome, ENT_QUOTES, 'UTF-8') ?></span></div>
            <div><span>Profissional</span><span><?= htmlspecialchars($agendamento->profissionalNome, ENT_QUOTES, 'UTF-8') ?></span></div>
            <div><span>Serviço</span><span><?= htmlspecialchars($agendamento->servicoNome, ENT_QUOTES, 'UTF-8') ?></span></div>
            <div><span>Data</span><span><?= (new DateTimeImmutable($agendamento->dataHora))->format('d/m/Y H:i') ?></span></div>
        </div>

        <?php if ($saldoAssinatura !== null): ?>
            <div class="form-alert <?= $saldoAssinatura['restantes'] > 0 ? 'form-alert-success' : '' ?>" style="margin-bottom: 1.5rem;">
                <div>
                    Cliente assinante do plano <strong><?= htmlspecialchars($saldoAssinatura['assinatura']->planoNome, ENT_QUOTES, 'UTF-8') ?></strong> -
                    <?= $saldoAssinatura['restantes'] ?> de <?= $saldoAssinatura['assinatura']->planoAtendimentosPorMes ?> atendimentos restantes esse ciclo.
                </div>
            </div>
            <?php if ($saldoAssinatura['restantes'] > 0): ?>
                <form method="POST" action="<?= $basePath ?>/dashboard/agendamentos/<?= $agendamento->id ?>/usar-assinatura" style="margin-bottom: 1.5rem;">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn-k btn-k-grad">Concluir usando a assinatura (sem cobrar)</button>
                </form>
                <p class="form-field-hint" style="margin: -1rem 0 1.5rem;">Ou registre um pagamento avulso abaixo, se preferir.</p>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/dashboard/agendamentos/<?= $agendamento->id ?>/pagamento">
            <?= Csrf::field() ?>

            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="forma_pagamento">Forma de pagamento</label>
                    <select id="forma_pagamento" name="forma_pagamento" required>
                        <option value="">Escolha...</option>
                        <?php foreach ($formasPagamento as $forma): ?>
                            <option value="<?= $forma ?>" <?= ($old['forma_pagamento'] ?? '') === $forma ? 'selected' : '' ?>>
                                <?= $labelFormaPagamento[$forma] ?? $forma ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="valor">Valor recebido (R$)</label>
                    <input type="text" id="valor" name="valor" inputmode="decimal" value="<?= htmlspecialchars((string) $valorSugerido, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>

            <?php if ($pixPayload !== null): ?>
                <div class="glass-card" data-pix-secao hidden style="margin: 0 0 1.5rem; padding: 1.25rem;">
                    <p style="margin: 0 0 0.6rem; font-weight: 600;">QR Code Pix da barbearia</p>
                    <p class="form-field-hint" style="margin: 0 0 0.8rem;">Mostre a tela pro cliente escanear no app do banco. O valor já vem preenchido (<?= htmlspecialchars((string) $valorSugerido, ENT_QUOTES, 'UTF-8') ?>).</p>
                    <div data-pix-qr></div>
                    <div class="pix-copiacola" style="margin-top: 0.8rem;">
                        <input type="text" value="<?= htmlspecialchars($pixPayload, ENT_QUOTES, 'UTF-8') ?>" readonly data-pix-copia-cola>
                        <button type="button" class="btn-k btn-k-outline btn-k-sm" data-pix-copiar>Copiar</button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad">Concluir e registrar pagamento</button>
                <a href="<?= $basePath ?>/dashboard/agendamentos" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>

<?php if ($pixPayload !== null): ?>
    <script>
        window.KADOSYS_PIX_PAYLOAD = <?= json_encode($pixPayload) ?>;
    </script>
    <script src="<?= $basePath ?>/assets/js/vendor/qrcode-generator.js?v=<?= View::assetVersion('assets/js/vendor/qrcode-generator.js') ?>"></script>
    <script src="<?= $basePath ?>/assets/js/pagamento-pix.js?v=<?= View::assetVersion('assets/js/pagamento-pix.js') ?>"></script>
<?php endif; ?>
