<?php
/**
 * @var array $config
 * @var \Igrejas\Models\FinanceiroLancamento|null $lancamento
 * @var array<int, \Igrejas\Models\FinanceiroCategoria> $categorias
 * @var array<int, \Igrejas\Models\Membro> $membrosAtivos
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $lancamento !== null;

$tipo = $old['tipo'] ?? $lancamento->tipo ?? 'entrada';
$categoriaId = $old['categoria_id'] ?? $lancamento->categoriaId ?? '';
$membroId = $old['membro_id'] ?? $lancamento->membroId ?? '';
$descricao = $old['descricao'] ?? $lancamento->descricao ?? '';
$valor = $old['valor'] ?? ($lancamento !== null ? number_format($lancamento->valor, 2, '.', '') : '');
$formaPagamento = $old['forma_pagamento'] ?? $lancamento->formaPagamento ?? 'dinheiro';
$dataLancamento = $old['data_lancamento'] ?? $lancamento->dataLancamento ?? date('Y-m-d');
$observacoes = $old['observacoes'] ?? $lancamento->observacoes ?? '';

$actionUrl = $isEdit
    ? $basePath . '/dashboard/financeiro/' . $lancamento->id
    : $basePath . '/dashboard/financeiro';

$formasPagamento = [
    'dinheiro' => 'Dinheiro',
    'pix' => 'Pix',
    'cartao' => 'Cartao',
    'transferencia' => 'Transferencia',
    'boleto' => 'Boleto',
    'outro' => 'Outro',
];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar lancamento' : 'Novo lancamento' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit ? 'Atualize os dados do lancamento.' : 'Registre uma entrada (dizimo, oferta) ou saida (despesa).' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/financeiro" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?php if ($errors !== []): ?>
    <div class="crud-alert error">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="dash-panel">
    <form method="POST" action="<?= $actionUrl ?>" class="crud-form">
        <?= $csrf ?>

        <div class="crud-form-section">
            <h3><i class="bi bi-cash-coin"></i> Dados do lancamento</h3>
            <div class="crud-form-grid">
                <div class="crud-field">
                    <label for="tipo">Tipo *</label>
                    <select id="tipo" name="tipo" data-financeiro-tipo required>
                        <option value="entrada" <?= $tipo === 'entrada' ? 'selected' : '' ?>>Entrada (dizimo, oferta, doacao...)</option>
                        <option value="saida" <?= $tipo === 'saida' ? 'selected' : '' ?>>Saida (despesa)</option>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="categoria_id">Categoria</label>
                    <select id="categoria_id" name="categoria_id" data-financeiro-categoria>
                        <option value="">Sem categoria</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <?php if ($categoria->status !== 'ativa' && (string) $categoria->id !== (string) $categoriaId) continue; ?>
                            <option value="<?= $categoria->id ?>" data-tipo="<?= $categoria->tipo ?>" <?= (string) $categoriaId === (string) $categoria->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria->nome, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="descricao">Descricao *</label>
                    <input type="text" id="descricao" name="descricao" value="<?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Dizimo do culto de domingo" required autofocus>
                </div>
                <div class="crud-field">
                    <label for="valor">Valor (R$) *</label>
                    <input type="number" id="valor" name="valor" value="<?= htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00" min="0.01" step="0.01" required>
                </div>
                <div class="crud-field">
                    <label for="data_lancamento">Data *</label>
                    <input type="date" id="data_lancamento" name="data_lancamento" value="<?= htmlspecialchars($dataLancamento, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="crud-field">
                    <label for="forma_pagamento">Forma de pagamento</label>
                    <select id="forma_pagamento" name="forma_pagamento">
                        <?php foreach ($formasPagamento as $valorOpcao => $rotulo): ?>
                            <option value="<?= $valorOpcao ?>" <?= $formaPagamento === $valorOpcao ? 'selected' : '' ?>><?= $rotulo ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="membro_id">Vincular a um membro (opcional)</label>
                    <select id="membro_id" name="membro_id">
                        <option value="">Nao vincular</option>
                        <?php foreach ($membrosAtivos as $membro): ?>
                            <option value="<?= $membro->id ?>" <?= (string) $membroId === (string) $membro->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="observacoes">Observacoes</label>
                    <textarea id="observacoes" name="observacoes" rows="3" placeholder="Detalhes adicionais (opcional)"><?= htmlspecialchars($observacoes, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/financeiro" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alteracoes' : 'Cadastrar lancamento' ?>
            </button>
        </div>
    </form>
</div>

<script src="<?= $basePath ?>/assets/js/financeiro-form.js?v=<?= \Igrejas\Core\View::assetVersion('assets/js/financeiro-form.js') ?>"></script>
