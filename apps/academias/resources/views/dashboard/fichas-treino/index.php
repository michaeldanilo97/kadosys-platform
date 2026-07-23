<?php

use Academias\Core\Csrf;
use Academias\Models\Aluno;
use Academias\Models\FichaTreino;

/**
 * @var array $config
 * @var array<int, array{ficha: FichaTreino, aluno: Aluno|null}> $fichas
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string|null $success
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Treino</p>
            <h1 class="dashboard-title">Fichas de Treino</h1>
            <p class="dash-page-subtitle"><?= $total ?> ficha<?= $total === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/fichas-treino/novo" class="btn-k btn-k-grad">+ Nova ficha</a>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <?php if ($fichas === []): ?>
            <p class="crud-empty">Nenhuma ficha de treino cadastrada ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Ficha</th>
                            <th>Aluno</th>
                            <th>Objetivo</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fichas as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['ficha']->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($item['aluno']->nome ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($item['ficha']->objetivo ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="status-badge <?= $item['ficha']->ativa ? 'ok' : 'dim' ?>"><?= $item['ficha']->ativa ? 'Ativa' : 'Inativa' ?></span></td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/fichas-treino/<?= $item['ficha']->id ?>/editar" class="crud-icon-btn" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/fichas-treino/<?= $item['ficha']->id ?>/excluir" data-confirm="Excluir esta ficha e todo o histórico de execução dela?">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Excluir"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                </td>
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
                            <a href="<?= $basePath ?>/dashboard/fichas-treino?pagina=<?= $p ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
