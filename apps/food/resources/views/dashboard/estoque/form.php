<?php

use Food\Core\Csrf;
use Food\Models\EstoqueMovimento;
use Food\Models\Ingrediente;

/**
 * @var array $config
 * @var array<int, Ingrediente> $ingredientes
 * @var array<int, string> $tiposValidos
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';

$labelTipo = [
    EstoqueMovimento::TIPO_ENTRADA => 'Entrada (soma ao estoque)',
    EstoqueMovimento::TIPO_SAIDA => 'Saída (retira do estoque)',
    EstoqueMovimento::TIPO_INVENTARIO => 'Inventário (define a contagem exata)',
    EstoqueMovimento::TIPO_PERDA => 'Perda/desperdício (retira do estoque)',
];

$tipoSelecionado = (string) ($old['tipo'] ?? '');
$ingredienteSelecionado = (string) ($old['ingrediente_id'] ?? '');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Compras</p>
            <h1 class="dashboard-title">Registrar movimentação</h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/estoque" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <?php if ($ingredientes === []): ?>
            <p class="crud-empty">Cadastre ingredientes antes de registrar uma movimentação.</p>
        <?php else: ?>
            <form method="POST" action="<?= $basePath ?>/dashboard/estoque/movimentar">
                <?= Csrf::field() ?>

                <div class="crud-form-grid">
                    <div class="form-field">
                        <label for="ingrediente_id">Ingrediente</label>
                        <select id="ingrediente_id" name="ingrediente_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($ingredientes as $ingrediente): ?>
                                <option value="<?= $ingrediente->id ?>" <?= $ingredienteSelecionado === (string) $ingrediente->id ? 'selected' : '' ?>><?= htmlspecialchars($ingrediente->nome, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($tiposValidos as $tipo): ?>
                                <option value="<?= $tipo ?>" <?= $tipoSelecionado === $tipo ? 'selected' : '' ?>><?= $labelTipo[$tipo] ?? $tipo ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="quantidade">Quantidade</label>
                        <input type="text" id="quantidade" name="quantidade" value="<?= htmlspecialchars((string) ($old['quantidade'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,000" required>
                        <span class="form-field-hint">No inventário, informe a nova contagem total (não a diferença).</span>
                    </div>
                    <div class="form-field crud-field-full">
                        <label for="motivo">Motivo (opcional)</label>
                        <input type="text" id="motivo" name="motivo" value="<?= htmlspecialchars((string) ($old['motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ajuste após contagem, quebra no preparo...">
                    </div>
                </div>

                <div class="crud-form-actions">
                    <button type="submit" class="btn-k btn-k-grad">Registrar</button>
                    <a href="<?= $basePath ?>/dashboard/estoque" class="btn-k btn-k-outline">Cancelar</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
