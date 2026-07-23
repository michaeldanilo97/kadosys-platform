<?php

use Food\Core\Csrf;
use Food\Core\View;
use Food\Models\Pedido;
use Food\Models\PedidoItem;
use Food\Models\PedidoPagamento;

/**
 * @var array $config
 * @var Pedido $pedido
 * @var array<int, PedidoItem> $itens
 * @var array<int, PedidoPagamento> $pagamentos
 * @var float $somaPaga
 * @var float $restante
 * @var string|null $pixPayload
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';

$labelFormaPagamento = [
    'dinheiro' => 'Dinheiro',
    'pix' => 'Pix',
    'cartao_credito' => 'Cartão de crédito',
    'cartao_debito' => 'Cartão de débito',
    'outro' => 'Vale/Outro',
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">PDV</p>
            <h1 class="dashboard-title">Pagamento - Pedido #<?= $pedido->id ?></h1>
            <p class="dash-page-subtitle">Total da venda: R$ <?= number_format($pedido->valorTotal, 2, ',', '.') ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/pdv" class="btn-k btn-k-outline">Voltar ao carrinho</a>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Resumo</h2>
        </div>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Total</th>
                        <th>Já pago</th>
                        <th>Restante</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>R$ <?= number_format($pedido->valorTotal, 2, ',', '.') ?></td>
                        <td class="text-dim">R$ <?= number_format($somaPaga, 2, ',', '.') ?></td>
                        <td><strong>R$ <?= number_format($restante, 2, ',', '.') ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($restante > 0): ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Adicionar forma de pagamento</h2>
            </div>
            <form method="POST" action="<?= $basePath ?>/dashboard/pdv/pagamento" class="crud-form-grid" data-pdv-forma-pagamento>
                <?= Csrf::field() ?>
                <div class="form-field">
                    <label for="forma_pagamento">Forma de pagamento</label>
                    <select id="forma_pagamento" name="forma_pagamento" required>
                        <?php foreach ($labelFormaPagamento as $valor => $label): ?>
                            <option value="<?= $valor ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field" data-campo-valor>
                    <label for="valor">Valor</label>
                    <input type="text" id="valor" name="valor" placeholder="0,00" value="<?= number_format($restante, 2, ',', '.') ?>">
                </div>
                <div class="form-field" data-campo-valor-recebido hidden>
                    <label for="valor_recebido">Valor recebido em dinheiro</label>
                    <input type="text" id="valor_recebido" name="valor_recebido" placeholder="0,00" value="<?= number_format($restante, 2, ',', '.') ?>">
                </div>
                <div class="form-field" style="align-self: flex-end;">
                    <button type="submit" class="btn-k btn-k-grad">Adicionar pagamento</button>
                </div>
            </form>

            <?php if ($pixPayload !== null): ?>
                <div class="pix-secao" style="margin-top: 1.5rem;">
                    <p class="text-dim">Pix (valor exato: R$ <?= number_format($restante, 2, ',', '.') ?>)</p>
                    <div data-pix-qr></div>
                    <div class="pix-copiacola" style="margin-top: 0.8rem;">
                        <input type="text" value="<?= htmlspecialchars($pixPayload, ENT_QUOTES, 'UTF-8') ?>" readonly data-pix-copia-cola>
                        <button type="button" class="btn-k btn-k-outline btn-k-sm" data-pix-copiar>Copiar</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($pagamentos !== []): ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Pagamentos registrados</h2>
            </div>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Forma</th>
                            <th>Valor aplicado</th>
                            <th>Recebido</th>
                            <th>Troco</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagamentos as $pagamento): ?>
                            <tr>
                                <td><?= $labelFormaPagamento[$pagamento->formaPagamento] ?? $pagamento->formaPagamento ?></td>
                                <td>R$ <?= number_format($pagamento->valor, 2, ',', '.') ?></td>
                                <td class="text-dim"><?= $pagamento->valorRecebido !== null ? 'R$ ' . number_format($pagamento->valorRecebido, 2, ',', '.') : '-' ?></td>
                                <td class="text-dim"><?= $pagamento->troco !== null ? 'R$ ' . number_format($pagamento->troco, 2, ',', '.') : '-' ?></td>
                                <td class="actions-col">
                                    <form method="POST" action="<?= $basePath ?>/dashboard/pdv/pagamento/<?= $pagamento->id ?>/excluir" data-confirm="Remover este pagamento?">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Remover"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($restante <= 0): ?>
        <div class="glass-card dash-panel">
            <form method="POST" action="<?= $basePath ?>/dashboard/pdv/finalizar">
                <?= Csrf::field() ?>
                <button type="submit" class="btn-k btn-k-grad" style="width: 100%;">Finalizar venda</button>
            </form>
        </div>
    <?php endif; ?>
</main>

<script>
    // So alterna qual campo (valor direto x valor recebido/troco) aparece
    // conforme a forma de pagamento escolhida - sem regra de negocio, so
    // exibicao (o calculo de troco de verdade acontece no servidor).
    (function () {
        var select = document.getElementById('forma_pagamento');
        var campoValor = document.querySelector('[data-campo-valor]');
        var campoValorRecebido = document.querySelector('[data-campo-valor-recebido]');

        if (!select || !campoValor || !campoValorRecebido) {
            return;
        }

        function atualizar() {
            var isDinheiro = select.value === 'dinheiro';
            campoValor.hidden = isDinheiro;
            campoValorRecebido.hidden = !isDinheiro;
        }

        select.addEventListener('change', atualizar);
        atualizar();
    })();
</script>

<?php if ($pixPayload !== null): ?>
    <script>
        window.KADOSYS_PIX_PAYLOAD = <?= json_encode($pixPayload) ?>;
    </script>
    <script src="<?= $basePath ?>/assets/js/vendor/qrcode-generator.js?v=<?= View::assetVersion('assets/js/vendor/qrcode-generator.js') ?>"></script>
<?php endif; ?>
<script src="<?= $basePath ?>/assets/js/pdv.js?v=<?= View::assetVersion('assets/js/pdv.js') ?>"></script>
