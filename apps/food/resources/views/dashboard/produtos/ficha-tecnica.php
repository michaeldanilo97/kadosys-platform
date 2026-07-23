<?php

use Food\Core\Csrf;
use Food\Models\FichaTecnicaItem;
use Food\Models\Ingrediente;
use Food\Models\Produto;

/**
 * @var array $config
 * @var Produto $produto
 * @var array<int, FichaTecnicaItem> $itens
 * @var array<int, Ingrediente> $ingredientes
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$baseUrl = $basePath . '/dashboard/produtos/' . $produto->id . '/ficha-tecnica';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Catálogo · <?= htmlspecialchars($produto->nome, ENT_QUOTES, 'UTF-8') ?></p>
            <h1 class="dashboard-title">Ficha técnica</h1>
            <p class="dash-page-subtitle">Rende <?= $produto->rendimento ?> unidade<?= $produto->rendimento === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/produtos/<?= $produto->id ?>/editar" class="btn-k btn-k-outline">Voltar ao produto</a>
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
        <div class="dash-panel-head">
            <h2>Custeio calculado</h2>
        </div>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Custo/un.</th>
                        <th>Markup</th>
                        <th>Margem</th>
                        <th>Ideal balcão</th>
                        <th>Ideal iFood</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>R$ <?= number_format($produto->custoTotal, 2, ',', '.') ?></td>
                        <td><?= number_format($produto->markup, 2, ',', '.') ?>x</td>
                        <td><?= number_format($produto->margemPercentual, 1, ',', '.') ?>%</td>
                        <td>R$ <?= number_format($produto->precoIdealBalcao, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($produto->precoIdealIfood, 2, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Ingredientes da receita</h2>
        </div>

        <?php if ($ingredientes === []): ?>
            <p class="crud-empty">Cadastre ingredientes antes de montar a ficha técnica.</p>
        <?php else: ?>
            <form method="POST" action="<?= $baseUrl ?>" class="crud-form-grid" style="margin-bottom: 1.5rem;">
                <?= Csrf::field() ?>
                <div class="form-field">
                    <label for="ingrediente_id">Ingrediente</label>
                    <select id="ingrediente_id" name="ingrediente_id" required>
                        <?php foreach ($ingredientes as $ingrediente): ?>
                            <option value="<?= $ingrediente->id ?>"><?= htmlspecialchars($ingrediente->nome, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="quantidade">Quantidade (no rendimento total)</label>
                    <input type="text" id="quantidade" name="quantidade" placeholder="0,000" required>
                </div>
                <div class="form-field">
                    <label for="perda_percentual">Perda (%)</label>
                    <input type="text" id="perda_percentual" name="perda_percentual" placeholder="0" value="0">
                </div>
                <div class="form-field" style="align-self: flex-end;">
                    <button type="submit" class="btn-k btn-k-grad">+ Adicionar ingrediente</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($itens === []): ?>
            <p class="crud-empty">Nenhum ingrediente na ficha técnica ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Quantidade</th>
                            <th>Perda</th>
                            <th>Custo do item</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <?php $custoItem = $item->quantidade * (1 + $item->perdaPercentual / 100) * $item->ingredientePrecoAtual; ?>
                            <tr>
                                <td><?= htmlspecialchars($item->ingredienteNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= rtrim(rtrim(number_format($item->quantidade, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($item->unidade, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= number_format($item->perdaPercentual, 1, ',', '.') ?>%</td>
                                <td class="text-dim">R$ <?= number_format($custoItem, 2, ',', '.') ?></td>
                                <td class="actions-col">
                                    <form method="POST" action="<?= $baseUrl ?>/<?= $item->id ?>/excluir" data-confirm="Remover este ingrediente da ficha técnica?">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Remover"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
