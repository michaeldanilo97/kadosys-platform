<?php

use Academias\Models\Academia;

/**
 * @var array $config
 * @var Academia $academia
 * @var array<int, array{checkin: \Academias\Models\AcademiaCheckin, aluno: \Academias\Models\Aluno}> $historico
 * @var int $total
 * @var int $page
 * @var int $lastPage
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Check-in</p>
            <h1 class="dashboard-title">Histórico</h1>
            <p class="dash-page-subtitle"><?= $total ?> registro<?= $total === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/checkin" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <div class="glass-card dash-panel">
        <?php if ($historico === []): ?>
            <p class="crud-empty">Nenhum check-in registrado ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Data</th>
                            <th>Entrada</th>
                            <th>Saída</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['aluno']->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= (new DateTimeImmutable($item['checkin']->entradaEm))->format('d/m/Y') ?></td>
                                <td class="text-dim"><?= (new DateTimeImmutable($item['checkin']->entradaEm))->format('H:i') ?></td>
                                <td class="text-dim"><?= $item['checkin']->saidaEm !== null ? (new DateTimeImmutable($item['checkin']->saidaEm))->format('H:i') : '-' ?></td>
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
                            <a href="<?= $basePath ?>/dashboard/checkin/historico?pagina=<?= $p ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
