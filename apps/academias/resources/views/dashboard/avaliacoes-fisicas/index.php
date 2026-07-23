<?php

use Academias\Core\Csrf;
use Academias\Models\Aluno;
use Academias\Models\AvaliacaoFisica;

/**
 * @var array $config
 * @var array<int, array{avaliacao: AvaliacaoFisica, aluno: Aluno|null}> $avaliacoes
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
            <h1 class="dashboard-title">Avaliação Física</h1>
            <p class="dash-page-subtitle"><?= $total ?> avaliaç<?= $total === 1 ? 'ão' : 'ões' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/avaliacoes-fisicas/novo" class="btn-k btn-k-grad">+ Nova avaliação</a>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <?php if ($avaliacoes === []): ?>
            <p class="crud-empty">Nenhuma avaliação física registrada ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Data</th>
                            <th>Peso</th>
                            <th>% Gordura</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($avaliacoes as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['aluno']->nome ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= (new DateTimeImmutable($item['avaliacao']->dataAvaliacao))->format('d/m/Y') ?></td>
                                <td class="text-dim"><?= number_format($item['avaliacao']->pesoKg, 1, ',', '.') ?> kg</td>
                                <td class="text-dim"><?= $item['avaliacao']->percentualGordura !== null ? number_format($item['avaliacao']->percentualGordura, 1, ',', '.') . '%' : '-' ?></td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/avaliacoes-fisicas/<?= $item['avaliacao']->id ?>/editar" class="crud-icon-btn" title="Editar">✏️</a>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/avaliacoes-fisicas/<?= $item['avaliacao']->id ?>/excluir" onsubmit="return confirm('Excluir esta avaliação?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Excluir">🗑️</button>
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
                            <a href="<?= $basePath ?>/dashboard/avaliacoes-fisicas?pagina=<?= $p ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
