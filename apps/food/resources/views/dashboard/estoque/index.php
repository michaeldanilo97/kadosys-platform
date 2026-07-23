<?php

use Food\Models\CompraItem;
use Food\Models\EstoqueMovimento;
use Food\Models\Ingrediente;

/**
 * @var array $config
 * @var array<int, Ingrediente> $estoqueBaixo
 * @var array<int, CompraItem> $vencendo
 * @var array<int, EstoqueMovimento> $movimentos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';

$labelTipo = [
    EstoqueMovimento::TIPO_ENTRADA => 'Entrada',
    EstoqueMovimento::TIPO_SAIDA => 'Saída',
    EstoqueMovimento::TIPO_INVENTARIO => 'Inventário',
    EstoqueMovimento::TIPO_PERDA => 'Perda',
];

$badgeTipo = [
    EstoqueMovimento::TIPO_ENTRADA => 'ok',
    EstoqueMovimento::TIPO_SAIDA => 'dim',
    EstoqueMovimento::TIPO_INVENTARIO => 'dim',
    EstoqueMovimento::TIPO_PERDA => 'danger',
];

$hoje = new DateTimeImmutable('today');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Compras</p>
            <h1 class="dashboard-title">Estoque</h1>
            <p class="dash-page-subtitle"><?= $total ?> <?= $total === 1 ? 'movimentação registrada' : 'movimentações registradas' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/estoque/movimentar" class="btn-k btn-k-grad">+ Registrar movimentação</a>
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

    <?php if ($estoqueBaixo !== []): ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Estoque baixo</h2>
            </div>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Estoque atual</th>
                            <th>Estoque mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estoqueBaixo as $ingrediente): ?>
                            <tr>
                                <td><?= htmlspecialchars($ingrediente->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="status-badge danger"><?= rtrim(rtrim(number_format($ingrediente->estoqueAtual, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="text-dim"><?= rtrim(rtrim(number_format($ingrediente->estoqueMinimo, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($ingrediente->unidade, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($vencendo !== []): ?>
        <div class="glass-card dash-panel">
            <div class="dash-panel-head">
                <h2>Vencendo em breve</h2>
            </div>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Quantidade</th>
                            <th>Validade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vencendo as $item): ?>
                            <?php $validade = new DateTimeImmutable((string) $item->validade); ?>
                            <tr>
                                <td><?= htmlspecialchars($item->ingredienteNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= rtrim(rtrim(number_format($item->quantidade, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($item->unidade, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="status-badge <?= $validade < $hoje ? 'danger' : 'dim' ?>">
                                        <?= $validade->format('d/m/Y') ?><?= $validade < $hoje ? ' (vencido)' : '' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Histórico de movimentações</h2>
        </div>

        <?php if ($movimentos === []): ?>
            <p class="crud-empty">Nenhuma movimentação registrada ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ingrediente</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimentos as $movimento): ?>
                            <tr>
                                <td class="text-dim"><?= (new DateTimeImmutable((string) $movimento->createdAt))->format('d/m/Y H:i') ?></td>
                                <td><?= htmlspecialchars($movimento->ingredienteNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="status-badge <?= $badgeTipo[$movimento->tipo] ?? 'dim' ?>"><?= $labelTipo[$movimento->tipo] ?? $movimento->tipo ?></span></td>
                                <td class="text-dim"><?= rtrim(rtrim(number_format($movimento->quantidade, 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($movimento->ingredienteUnidade, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($movimento->motivo ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($lastPage > 1): ?>
                <div class="crud-pagination">
                    <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                        <?php if ($p === $page): ?>
                            <span class="atual"><?= $p ?></span>
                        <?php else: ?>
                            <a href="<?= $basePath ?>/dashboard/estoque?pagina=<?= $p ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
