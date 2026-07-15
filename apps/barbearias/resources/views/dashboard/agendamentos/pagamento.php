<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Agendamento;

/**
 * @var array $config
 * @var Agendamento $agendamento
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

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad">Concluir e registrar pagamento</button>
                <a href="<?= $basePath ?>/dashboard/agendamentos" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
