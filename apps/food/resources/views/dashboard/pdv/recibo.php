<?php

use Food\Models\Pedido;
use Food\Models\PedidoItem;
use Food\Models\PedidoPagamento;
use Food\Models\Restaurante;

/**
 * Recibo simples pra impressao pelo proprio navegador (Ctrl+P /
 * window.print()) - sem driver de impressora termica/ESC-POS, que
 * exigiria integracao especifica de hardware fora de escopo aqui.
 *
 * @var array $config
 * @var Restaurante|null $restaurante
 * @var Pedido $pedido
 * @var array<int, PedidoItem> $itens
 * @var array<int, PedidoPagamento> $pagamentos
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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Recibo', ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: 'Courier New', monospace; max-width: 340px; margin: 1.5rem auto; color: #111; }
        h1 { font-size: 1rem; text-align: center; margin: 0 0 0.2rem; }
        .recibo-sub { text-align: center; font-size: 0.75rem; color: #444; margin: 0 0 1rem; }
        hr { border: none; border-top: 1px dashed #999; margin: 0.6rem 0; }
        table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        td { padding: 0.15rem 0; }
        .col-valor { text-align: right; }
        .recibo-total { font-size: 1rem; font-weight: bold; }
        .recibo-acoes { text-align: center; margin-top: 1.5rem; }
        .recibo-acoes button { font-size: 0.9rem; padding: 0.5rem 1.2rem; cursor: pointer; }
        @media print {
            .recibo-acoes { display: none; }
        }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($restaurante->nome ?? 'KADOSYS Food', ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="recibo-sub">Pedido #<?= $pedido->id ?> · <?= (new DateTimeImmutable((string) $pedido->createdAt))->format('d/m/Y H:i') ?></p>
    <hr>
    <table>
        <?php foreach ($itens as $item): ?>
            <tr>
                <td><?= $item->quantidade ?>x <?= htmlspecialchars($item->produtoNome, ENT_QUOTES, 'UTF-8') ?></td>
                <td class="col-valor">R$ <?= number_format($item->subtotal, 2, ',', '.') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <hr>
    <table>
        <tr>
            <td>Subtotal</td>
            <td class="col-valor">R$ <?= number_format($pedido->subtotal, 2, ',', '.') ?></td>
        </tr>
        <?php if ($pedido->desconto > 0): ?>
            <tr>
                <td>Desconto</td>
                <td class="col-valor">- R$ <?= number_format($pedido->desconto, 2, ',', '.') ?></td>
            </tr>
        <?php endif; ?>
        <tr class="recibo-total">
            <td>Total</td>
            <td class="col-valor">R$ <?= number_format($pedido->valorTotal, 2, ',', '.') ?></td>
        </tr>
    </table>
    <hr>
    <table>
        <?php foreach ($pagamentos as $pagamento): ?>
            <tr>
                <td><?= $labelFormaPagamento[$pagamento->formaPagamento] ?? $pagamento->formaPagamento ?></td>
                <td class="col-valor">R$ <?= number_format($pagamento->valor, 2, ',', '.') ?></td>
            </tr>
            <?php if ($pagamento->troco !== null && $pagamento->troco > 0): ?>
                <tr>
                    <td>Troco</td>
                    <td class="col-valor">R$ <?= number_format($pagamento->troco, 2, ',', '.') ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
    <hr>
    <p class="recibo-sub">Obrigado pela preferência!</p>

    <div class="recibo-acoes">
        <button type="button" data-print-btn>Imprimir recibo</button>
        <p><a href="<?= $basePath ?>/dashboard/pdv">Nova venda</a></p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var botao = document.querySelector('[data-print-btn]');
            if (botao) {
                botao.addEventListener('click', function () {
                    window.print();
                });
            }
        });
    </script>
</body>
</html>
