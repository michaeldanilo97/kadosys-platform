<?php

use Food\Core\Csrf;
use Food\Models\Fornecedor;
use Food\Models\Ingrediente;

/**
 * @var array $config
 * @var Ingrediente|null $ingrediente
 * @var array<int, Fornecedor> $fornecedores
 * @var array<int, string> $unidades
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $ingrediente !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/ingredientes/' . $ingrediente->id : $basePath . '/dashboard/ingredientes';
$unidadeSelecionada = $old['unidade'] ?? $ingrediente->unidade ?? 'un';
$fornecedorSelecionado = (string) ($old['fornecedor_id'] ?? $ingrediente->fornecedorId ?? '');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Catálogo</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar ingrediente' : 'Novo ingrediente' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/ingredientes" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="POST" action="<?= $actionUrl ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>

            <div class="crud-form-grid">
                <div class="form-field crud-field-full">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $ingrediente->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <div class="form-field">
                    <label for="categoria">Categoria</label>
                    <input type="text" id="categoria" name="categoria" value="<?= htmlspecialchars($old['categoria'] ?? $ingrediente->categoria ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Laticínios, embalagens...">
                    <span class="form-field-hint">Texto livre - organiza a lista, não é a categoria do produto vendido.</span>
                </div>
                <div class="form-field">
                    <label for="fornecedor_id">Fornecedor</label>
                    <select id="fornecedor_id" name="fornecedor_id">
                        <option value="">Sem fornecedor</option>
                        <?php foreach ($fornecedores as $fornecedor): ?>
                            <option value="<?= $fornecedor->id ?>" <?= $fornecedorSelecionado === (string) $fornecedor->id ? 'selected' : '' ?>><?= htmlspecialchars($fornecedor->nome, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="codigo">Código/SKU</label>
                    <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($old['codigo'] ?? $ingrediente->codigo ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="unidade">Unidade de medida</label>
                    <select id="unidade" name="unidade" required>
                        <?php foreach ($unidades as $unidade): ?>
                            <option value="<?= $unidade ?>" <?= $unidadeSelecionada === $unidade ? 'selected' : '' ?>><?= strtoupper($unidade) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="preco_atual">Preço atual (por unidade)</label>
                    <input type="text" id="preco_atual" name="preco_atual" value="<?= htmlspecialchars((string) ($old['preco_atual'] ?? ($ingrediente ? number_format($ingrediente->precoAtual, 2, ',', '') : '')), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00">
                </div>
                <div class="form-field">
                    <label for="estoque_atual">Estoque atual</label>
                    <input type="text" id="estoque_atual" name="estoque_atual" value="<?= htmlspecialchars((string) ($old['estoque_atual'] ?? ($ingrediente ? number_format($ingrediente->estoqueAtual, 3, ',', '') : '0')), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="estoque_minimo">Estoque mínimo</label>
                    <input type="text" id="estoque_minimo" name="estoque_minimo" value="<?= htmlspecialchars((string) ($old['estoque_minimo'] ?? ($ingrediente ? number_format($ingrediente->estoqueMinimo, 3, ',', '') : '0')), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="localizacao">Localização no estoque</label>
                    <input type="text" id="localizacao" name="localizacao" value="<?= htmlspecialchars($old['localizacao'] ?? $ingrediente->localizacao ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Prateleira 3, câmara fria...">
                </div>
                <div class="form-field">
                    <label for="foto">Foto</label>
                    <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp">
                    <span class="form-field-hint">PNG, JPG ou WEBP - até 5MB.</span>
                    <?php if ($isEdit && $ingrediente->fotoPath): ?>
                        <img src="<?= $basePath ?>/<?= htmlspecialchars($ingrediente->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Foto atual" class="foto-preview">
                    <?php endif; ?>
                </div>
                <div class="form-field crud-field-full">
                    <label for="observacao">Observação</label>
                    <textarea id="observacao" name="observacao" rows="3"><?= htmlspecialchars($old['observacao'] ?? $ingrediente->observacao ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <?php if ($isEdit): ?>
                    <div class="form-field crud-checkbox-field">
                        <input type="checkbox" id="ativo" name="ativo" value="1" <?= $ingrediente->ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;">Ingrediente ativo</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/ingredientes" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
