<?php
/**
 * @var array $config
 * @var \Igrejas\Models\PatrimonioBem|null $bem
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $bem !== null;

$nome = $old['nome'] ?? $bem->nome ?? '';
$categoria = $old['categoria'] ?? $bem->categoria ?? 'outro';
$numeroPatrimonio = $old['numero_patrimonio'] ?? $bem->numeroPatrimonio ?? '';
$descricao = $old['descricao'] ?? $bem->descricao ?? '';
$valorEstimado = $old['valor_estimado'] ?? ($bem !== null && $bem->valorEstimado !== null ? number_format($bem->valorEstimado, 2, '.', '') : '');
$dataAquisicao = $old['data_aquisicao'] ?? $bem->dataAquisicao ?? '';
$local = $old['local'] ?? $bem->local ?? '';
$status = $old['status'] ?? $bem->status ?? 'ativo';
$observacoes = $old['observacoes'] ?? $bem->observacoes ?? '';

$actionUrl = $isEdit
    ? $basePath . '/dashboard/patrimonio/' . $bem->id
    : $basePath . '/dashboard/patrimonio';

$categorias = [
    'imovel' => 'Imovel', 'veiculo' => 'Veiculo', 'equipamento' => 'Equipamento',
    'mobiliario' => 'Mobiliario', 'eletronico' => 'Eletronico', 'outro' => 'Outro',
];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar bem' : 'Novo bem' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize os dados de ' . htmlspecialchars($bem->nome, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados para cadastrar um novo bem, imovel ou equipamento.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/patrimonio" class="btn-k btn-k-ghost">
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
            <h3><i class="bi bi-building"></i> Dados do bem</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="nome">Nome *</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Som principal do templo" required autofocus>
                </div>
                <div class="crud-field">
                    <label for="categoria">Categoria</label>
                    <select id="categoria" name="categoria">
                        <?php foreach ($categorias as $valor => $rotulo): ?>
                            <option value="<?= $valor ?>" <?= $categoria === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="numero_patrimonio">Numero de patrimonio</label>
                    <input type="text" id="numero_patrimonio" name="numero_patrimonio" value="<?= htmlspecialchars($numeroPatrimonio, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: PAT-0042">
                </div>
                <div class="crud-field">
                    <label for="valor_estimado">Valor estimado (R$)</label>
                    <input type="number" id="valor_estimado" name="valor_estimado" value="<?= htmlspecialchars((string) $valorEstimado, ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00" min="0" step="0.01">
                </div>
                <div class="crud-field">
                    <label for="data_aquisicao">Data de aquisicao</label>
                    <input type="date" id="data_aquisicao" name="data_aquisicao" value="<?= htmlspecialchars($dataAquisicao, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="local">Local</label>
                    <input type="text" id="local" name="local" value="<?= htmlspecialchars($local, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Templo principal">
                </div>
                <div class="crud-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="manutencao" <?= $status === 'manutencao' ? 'selected' : '' ?>>Em manutencao</option>
                        <option value="baixado" <?= $status === 'baixado' ? 'selected' : '' ?>>Baixado</option>
                    </select>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="descricao">Descricao</label>
                    <textarea id="descricao" name="descricao" rows="2" placeholder="Detalhes do bem (opcional)"><?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="observacoes">Observacoes</label>
                    <textarea id="observacoes" name="observacoes" rows="2" placeholder="Historico de manutencao, garantia, etc. (opcional)"><?= htmlspecialchars($observacoes, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/patrimonio" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alteracoes' : 'Cadastrar bem' ?>
            </button>
        </div>
    </form>
</div>
