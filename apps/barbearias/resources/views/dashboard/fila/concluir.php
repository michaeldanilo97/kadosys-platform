<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\FilaAtendimento;

/**
 * @var array $config
 * @var FilaAtendimento $item
 * @var array<int, string> $formasPagamento
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
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Fila</p>
            <h1 class="dashboard-title">Concluir e registrar pagamento</h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/fila" class="btn-k btn-k-outline">Voltar</a>
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
            <div><span>Cliente</span><span><?= htmlspecialchars($item->nome, ENT_QUOTES, 'UTF-8') ?></span></div>
            <?php if ($item->telefone !== null): ?>
                <div><span>Telefone</span><span><?= htmlspecialchars($item->telefone, ENT_QUOTES, 'UTF-8') ?></span></div>
            <?php endif; ?>
        </div>

        <form method="POST" action="<?= $basePath ?>/dashboard/fila/<?= $item->id ?>/concluir">
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
                    <input type="text" id="valor" name="valor" inputmode="decimal" value="<?= htmlspecialchars((string) ($old['valor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad">Concluir e registrar pagamento</button>
                <a href="<?= $basePath ?>/dashboard/fila" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
