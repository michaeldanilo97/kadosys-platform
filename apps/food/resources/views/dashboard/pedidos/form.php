<?php

use Food\Core\Csrf;
use Food\Models\Cliente;
use Food\Models\Pedido;

/**
 * @var array $config
 * @var array<int, Cliente> $clientes
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';

$labelOrigem = [
    Pedido::ORIGEM_BALCAO => 'Balcão',
    Pedido::ORIGEM_WHATSAPP => 'WhatsApp',
    Pedido::ORIGEM_IFOOD_MANUAL => 'iFood (registro manual)',
    Pedido::ORIGEM_DELIVERY_PROPRIO => 'Delivery próprio',
];

$labelFormaPagamento = [
    'dinheiro' => 'Dinheiro',
    'pix' => 'Pix',
    'cartao_credito' => 'Cartão de crédito',
    'cartao_debito' => 'Cartão de débito',
    'outro' => 'Outro',
];

$clienteSelecionado = (string) ($old['cliente_id'] ?? '');
$origemSelecionada = (string) ($old['origem'] ?? Pedido::ORIGEM_BALCAO);
$formaSelecionada = (string) ($old['forma_pagamento'] ?? 'dinheiro');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Vendas</p>
            <h1 class="dashboard-title">Novo pedido</h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/pedidos" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="POST" action="<?= $basePath ?>/dashboard/pedidos">
            <?= Csrf::field() ?>

            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="cliente_id">Cliente</label>
                    <select id="cliente_id" name="cliente_id">
                        <option value="">Sem cliente identificado</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= $cliente->id ?>" <?= $clienteSelecionado === (string) $cliente->id ? 'selected' : '' ?>><?= htmlspecialchars($cliente->nome, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="origem">Origem</label>
                    <select id="origem" name="origem" required>
                        <?php foreach ($labelOrigem as $origem => $label): ?>
                            <option value="<?= $origem ?>" <?= $origemSelecionada === $origem ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="forma_pagamento">Forma de pagamento</label>
                    <select id="forma_pagamento" name="forma_pagamento" required>
                        <?php foreach ($labelFormaPagamento as $forma => $label): ?>
                            <option value="<?= $forma ?>" <?= $formaSelecionada === $forma ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="cupom">Cupom (opcional)</label>
                    <input type="text" id="cupom" name="cupom" value="<?= htmlspecialchars((string) ($old['cupom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="PROMO10">
                </div>
                <div class="form-field">
                    <label for="desconto">Desconto (R$)</label>
                    <input type="text" id="desconto" name="desconto" value="<?= htmlspecialchars((string) ($old['desconto'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00">
                </div>
                <div class="form-field crud-field-full">
                    <label for="endereco_entrega">Endereço de entrega (se houver)</label>
                    <textarea id="endereco_entrega" name="endereco_entrega" rows="2"><?= htmlspecialchars((string) ($old['endereco_entrega'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="form-field crud-field-full">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="2"><?= htmlspecialchars((string) ($old['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <p class="dash-page-subtitle">Depois de criar o pedido, você adiciona os itens (produtos) na tela seguinte.</p>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad">Criar pedido</button>
                <a href="<?= $basePath ?>/dashboard/pedidos" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
