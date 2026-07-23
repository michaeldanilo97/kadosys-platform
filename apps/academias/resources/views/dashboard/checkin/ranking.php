<?php

use Academias\Models\Academia;

/**
 * @var array $config
 * @var Academia $academia
 * @var array<int, array{aluno: \Academias\Models\Aluno, checkinsNoMes: int}> $ranking
 */
$basePath = $config['base_path'] ?? '';
$medalhas = ['🥇', '🥈', '🥉'];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Check-in</p>
            <h1 class="dashboard-title">Ranking de frequência</h1>
            <p class="dash-page-subtitle">Check-ins deste mês, do mais assíduo pro menos.</p>
        </div>
        <a href="<?= $basePath ?>/dashboard/checkin" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <div class="glass-card dash-panel">
        <?php if ($ranking === []): ?>
            <p class="crud-empty">Nenhum check-in registrado este mês ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Aluno</th>
                            <th>Check-ins no mês</th>
                            <th>Sequência atual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ranking as $i => $item): ?>
                            <tr>
                                <td><?= $medalhas[$i] ?? ($i + 1) ?></td>
                                <td><?= htmlspecialchars($item['aluno']->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $item['checkinsNoMes'] ?></td>
                                <td class="text-dim">🔥 <?= $item['aluno']->streakAtual ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
