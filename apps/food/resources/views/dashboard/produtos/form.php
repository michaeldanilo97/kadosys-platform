<?php

use Food\Core\Csrf;
use Food\Models\Categoria;
use Food\Models\Produto;

/**
 * @var array $config
 * @var Produto|null $produto
 * @var array<int, Categoria> $categorias
 * @var array<int, string> $statusValidos
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $produto !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/produtos/' . $produto->id : $basePath . '/dashboard/produtos';

$labelStatus = [
    Produto::STATUS_ATIVO => 'Ativo',
    Produto::STATUS_PAUSADO => 'Pausado',
    Produto::STATUS_INATIVO => 'Inativo',
];

$categoriaSelecionada = (string) ($old['categoria_id'] ?? $produto->categoriaId ?? '');
$statusSelecionado = (string) ($old['status'] ?? $produto->status ?? Produto::STATUS_ATIVO);

$valor = static function (string $campo, ?float $valorProduto, int $casas = 2) use ($old): string {
    if (isset($old[$campo])) {
        return (string) $old[$campo];
    }

    return $valorProduto !== null ? number_format($valorProduto, $casas, ',', '') : '';
};
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Catálogo</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar produto' : 'Novo produto' ?></h1>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <?php if ($isEdit): ?>
                <a href="<?= $basePath ?>/dashboard/produtos/<?= $produto->id ?>/ficha-tecnica" class="btn-k btn-k-outline">Ficha técnica</a>
            <?php endif; ?>
            <a href="<?= $basePath ?>/dashboard/produtos" class="btn-k btn-k-outline">Voltar</a>
        </div>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($isEdit): ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Custeio calculado</h2>
                <p class="dash-page-subtitle">Recalculado automaticamente pela ficha técnica - ver aba "Ficha técnica".</p>
            </div>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Custo/un.</th>
                            <th>Markup</th>
                            <th>Margem</th>
                            <th>Lucro/un.</th>
                            <th>Ideal balcão</th>
                            <th>Ideal WhatsApp</th>
                            <th>Ideal iFood</th>
                            <th>Ideal delivery</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>R$ <?= number_format($produto->custoTotal, 2, ',', '.') ?></td>
                            <td><?= number_format($produto->markup, 2, ',', '.') ?>x</td>
                            <td><?= number_format($produto->margemPercentual, 1, ',', '.') ?>%</td>
                            <td>R$ <?= number_format($produto->lucro, 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($produto->precoIdealBalcao, 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($produto->precoIdealWhatsapp, 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($produto->precoIdealIfood, 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($produto->precoIdealDelivery, 2, ',', '.') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="POST" action="<?= $actionUrl ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>

            <div class="crud-form-grid">
                <div class="form-field crud-field-full">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $produto->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <div class="form-field">
                    <label for="categoria_id">Categoria</label>
                    <select id="categoria_id" name="categoria_id">
                        <option value="">Sem categoria</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria->id ?>" <?= $categoriaSelecionada === (string) $categoria->id ? 'selected' : '' ?>><?= htmlspecialchars($categoria->nome, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="codigo">Código/SKU</label>
                    <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($old['codigo'] ?? $produto->codigo ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="codigo_barras">Código de barras</label>
                    <input type="text" id="codigo_barras" name="codigo_barras" value="<?= htmlspecialchars($old['codigo_barras'] ?? $produto->codigoBarras ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <?php if ($isEdit): ?>
                    <div class="form-field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach ($statusValidos as $status): ?>
                                <option value="<?= $status ?>" <?= $statusSelecionado === $status ? 'selected' : '' ?>><?= $labelStatus[$status] ?? $status ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="form-field crud-field-full">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="2"><?= htmlspecialchars($old['descricao'] ?? $produto->descricao ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="form-field">
                    <label for="tags">Tags</label>
                    <input type="text" id="tags" name="tags" value="<?= htmlspecialchars($old['tags'] ?? $produto->tags ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="vegano, sem-gluten, mais-vendido">
                </div>
                <div class="form-field">
                    <label for="foto">Foto</label>
                    <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp">
                    <span class="form-field-hint">PNG, JPG ou WEBP - até 5MB.</span>
                    <?php if ($isEdit && $produto->fotoPath): ?>
                        <img src="<?= $basePath ?>/<?= htmlspecialchars($produto->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Foto atual" class="foto-preview">
                    <?php endif; ?>
                </div>
            </div>

            <h3 class="crud-section-title">Preços por canal</h3>
            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="preco_balcao">Preço balcão</label>
                    <input type="text" id="preco_balcao" name="preco_balcao" value="<?= htmlspecialchars($valor('preco_balcao', $produto->precoBalcao ?? null), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00" required>
                </div>
                <div class="form-field">
                    <label for="preco_whatsapp">Preço WhatsApp</label>
                    <input type="text" id="preco_whatsapp" name="preco_whatsapp" value="<?= htmlspecialchars($valor('preco_whatsapp', $produto->precoWhatsapp ?? null), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00">
                </div>
                <div class="form-field">
                    <label for="preco_ifood">Preço iFood</label>
                    <input type="text" id="preco_ifood" name="preco_ifood" value="<?= htmlspecialchars($valor('preco_ifood', $produto->precoIfood ?? null), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00">
                </div>
                <div class="form-field">
                    <label for="preco_promocao">Preço promoção</label>
                    <input type="text" id="preco_promocao" name="preco_promocao" value="<?= htmlspecialchars($valor('preco_promocao', $produto->precoPromocao ?? null), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00">
                </div>
                <div class="form-field">
                    <label for="preco_delivery_proprio">Preço delivery próprio</label>
                    <input type="text" id="preco_delivery_proprio" name="preco_delivery_proprio" value="<?= htmlspecialchars($valor('preco_delivery_proprio', $produto->precoDeliveryProprio ?? null), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00">
                </div>
            </div>

            <h3 class="crud-section-title">Produção</h3>
            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="tempo_preparo_min">Tempo de preparo (min)</label>
                    <input type="number" id="tempo_preparo_min" name="tempo_preparo_min" min="0" value="<?= htmlspecialchars((string) ($old['tempo_preparo_min'] ?? $produto->tempoPreparoMin ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="peso_g">Peso (g)</label>
                    <input type="text" id="peso_g" name="peso_g" value="<?= htmlspecialchars($valor('peso_g', $produto->pesoG ?? null), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="rendimento">Rendimento da receita (un.)</label>
                    <input type="number" id="rendimento" name="rendimento" min="1" value="<?= htmlspecialchars((string) ($old['rendimento'] ?? $produto->rendimento ?? 1), ENT_QUOTES, 'UTF-8') ?>" required>
                    <span class="form-field-hint">Quantas unidades a ficha técnica rende (ex.: um bolo que rende 12 fatias).</span>
                </div>
                <div class="form-field crud-field-full">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="2"><?= htmlspecialchars($old['observacoes'] ?? $produto->observacoes ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <h3 class="crud-section-title">Custeio avançado (opcional)</h3>
            <p class="dash-page-subtitle">Deixe em branco pra usar o valor padrão da loja (Configurações → Custeio, em breve). Preencha só se este produto tiver um custo de overhead diferente do padrão.</p>
            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="custo_energia_override">Energia (por unidade)</label>
                    <input type="text" id="custo_energia_override" name="custo_energia_override" value="<?= htmlspecialchars($valor('custo_energia_override', $produto->custoEnergiaOverride ?? null, 4), ENT_QUOTES, 'UTF-8') ?>" placeholder="Padrão da loja">
                </div>
                <div class="form-field">
                    <label for="custo_gas_override">Gás (por unidade)</label>
                    <input type="text" id="custo_gas_override" name="custo_gas_override" value="<?= htmlspecialchars($valor('custo_gas_override', $produto->custoGasOverride ?? null, 4), ENT_QUOTES, 'UTF-8') ?>" placeholder="Padrão da loja">
                </div>
                <div class="form-field">
                    <label for="custo_agua_override">Água (por unidade)</label>
                    <input type="text" id="custo_agua_override" name="custo_agua_override" value="<?= htmlspecialchars($valor('custo_agua_override', $produto->custoAguaOverride ?? null, 4), ENT_QUOTES, 'UTF-8') ?>" placeholder="Padrão da loja">
                </div>
                <div class="form-field">
                    <label for="custo_embalagem_override">Embalagem (por unidade)</label>
                    <input type="text" id="custo_embalagem_override" name="custo_embalagem_override" value="<?= htmlspecialchars($valor('custo_embalagem_override', $produto->custoEmbalagemOverride ?? null, 4), ENT_QUOTES, 'UTF-8') ?>" placeholder="Padrão da loja">
                </div>
                <div class="form-field">
                    <label for="custo_etiqueta_override">Etiqueta (por unidade)</label>
                    <input type="text" id="custo_etiqueta_override" name="custo_etiqueta_override" value="<?= htmlspecialchars($valor('custo_etiqueta_override', $produto->custoEtiquetaOverride ?? null, 4), ENT_QUOTES, 'UTF-8') ?>" placeholder="Padrão da loja">
                </div>
                <div class="form-field">
                    <label for="custo_mao_obra_override">Mão de obra (por unidade)</label>
                    <input type="text" id="custo_mao_obra_override" name="custo_mao_obra_override" value="<?= htmlspecialchars($valor('custo_mao_obra_override', $produto->custoMaoObraOverride ?? null, 4), ENT_QUOTES, 'UTF-8') ?>" placeholder="Padrão da loja">
                </div>
                <div class="form-field">
                    <label for="custo_taxa_operacional_override">Taxa operacional (por unidade)</label>
                    <input type="text" id="custo_taxa_operacional_override" name="custo_taxa_operacional_override" value="<?= htmlspecialchars($valor('custo_taxa_operacional_override', $produto->custoTaxaOperacionalOverride ?? null, 4), ENT_QUOTES, 'UTF-8') ?>" placeholder="Padrão da loja">
                </div>
                <div class="form-field">
                    <label for="custo_desperdicio_override">Desperdício (por unidade)</label>
                    <input type="text" id="custo_desperdicio_override" name="custo_desperdicio_override" value="<?= htmlspecialchars($valor('custo_desperdicio_override', $produto->custoDesperdicioOverride ?? null, 4), ENT_QUOTES, 'UTF-8') ?>" placeholder="Padrão da loja">
                </div>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/produtos" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
