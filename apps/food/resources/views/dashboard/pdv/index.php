<?php

use Food\Core\Csrf;
use Food\Core\View;
use Food\Models\Pedido;
use Food\Models\PedidoItem;
use Food\Models\Produto;

/**
 * @var array $config
 * @var Pedido $pedido
 * @var array<int, PedidoItem> $itens
 * @var array<int, Produto> $produtos
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';

$produtosParaJs = array_map(
    static fn (Produto $produto): array => [
        'id' => $produto->id,
        'codigoBarras' => $produto->codigoBarras,
    ],
    array_values(array_filter($produtos, static fn (Produto $p): bool => $p->codigoBarras !== null && $p->codigoBarras !== '')),
);
?>
<main class="dashboard-main pdv-shell" data-pdv-shell data-produtos="<?= htmlspecialchars(json_encode($produtosParaJs, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Venda</p>
            <h1 class="dashboard-title">PDV - Pedido #<?= $pedido->id ?></h1>
            <p class="dash-page-subtitle">Toque num produto ou leia o código de barras</p>
        </div>
        <form method="POST" action="<?= $basePath ?>/dashboard/pdv/cancelar" data-confirm="Cancelar esta venda e limpar o carrinho?">
            <?= Csrf::field() ?>
            <button type="submit" class="btn-k btn-k-outline">Cancelar venda</button>
        </form>
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
        <div class="form-field" style="max-width: 360px;">
            <label for="pdv_codigo_barras">Código de barras</label>
            <input type="text" id="pdv_codigo_barras" data-codigo-barras-input placeholder="Leia ou digite o código..." autocomplete="off" autofocus>
            <p class="form-field-hint" data-codigo-barras-hint hidden></p>
        </div>
        <form method="POST" action="<?= $basePath ?>/dashboard/pdv/itens" data-form-codigo-barras hidden>
            <?= Csrf::field() ?>
            <input type="hidden" name="produto_id" data-campo-produto-id value="">
            <input type="hidden" name="quantidade" value="1">
        </form>
    </div>

    <div class="pdv-grid-layout">
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Produtos</h2>
            </div>

            <?php if ($produtos === []): ?>
                <p class="crud-empty">Cadastre produtos ativos antes de vender pelo PDV.</p>
            <?php else: ?>
                <div class="pdv-produtos-grid">
                    <?php foreach ($produtos as $produto): ?>
                        <form method="POST" action="<?= $basePath ?>/dashboard/pdv/itens">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="produto_id" value="<?= $produto->id ?>">
                            <input type="hidden" name="quantidade" value="1">
                            <button type="submit" class="pdv-tile">
                                <span class="pdv-tile-nome"><?= htmlspecialchars($produto->nome, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="pdv-tile-preco">R$ <?= number_format($produto->precoBalcao, 2, ',', '.') ?></span>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-card dash-panel pdv-carrinho">
            <div class="dash-panel-head">
                <h2>Carrinho</h2>
            </div>

            <?php if ($itens === []): ?>
                <p class="crud-empty">Nenhum item ainda.</p>
            <?php else: ?>
                <ul class="pdv-carrinho-itens">
                    <?php foreach ($itens as $item): ?>
                        <li>
                            <div>
                                <strong><?= $item->quantidade ?>x</strong> <?= htmlspecialchars($item->produtoNome, ENT_QUOTES, 'UTF-8') ?>
                                <span class="text-dim">R$ <?= number_format($item->subtotal, 2, ',', '.') ?></span>
                            </div>
                            <form method="POST" action="<?= $basePath ?>/dashboard/pdv/itens/<?= $item->id ?>/excluir">
                                <?= Csrf::field() ?>
                                <button type="submit" class="crud-icon-btn danger" title="Remover"><i class="bi bi-trash-fill"></i></button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="pdv-carrinho-total">
                <span>Total</span>
                <strong>R$ <?= number_format($pedido->valorTotal, 2, ',', '.') ?></strong>
            </div>

            <a href="<?= $basePath ?>/dashboard/pdv/pagamento" class="btn-k btn-k-grad" style="width: 100%; text-align: center; display: block;<?= $itens === [] ? ' pointer-events: none; opacity: .5;' : '' ?>">
                Ir para pagamento
            </a>
        </div>
    </div>
</main>

<script src="<?= $basePath ?>/assets/js/pdv.js?v=<?= View::assetVersion('assets/js/pdv.js') ?>"></script>
