<?php

use Academias\Models\Academia;
use Academias\Models\Aluno;

/**
 * @var array $config
 * @var Academia $academia
 * @var array<int, array{checkin: \Academias\Models\AcademiaCheckin, aluno: Aluno}> $presentes
 * @var string|null $success
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Check-in</p>
            <h1 class="dashboard-title">Quem está na academia agora</h1>
            <p class="dash-page-subtitle"><?= count($presentes) ?> aluno<?= count($presentes) === 1 ? '' : 's' ?> dentro</p>
        </div>
        <div style="display:flex; gap:0.75rem;">
            <a href="<?= $basePath ?>/dashboard/checkin/historico" class="btn-k btn-k-outline">Histórico</a>
            <a href="<?= $basePath ?>/dashboard/ranking" class="btn-k btn-k-outline">Ranking</a>
            <a href="<?= $basePath ?>/dashboard/checkin/qr" class="btn-k btn-k-grad">📱 Ver QR</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <?php if ($presentes === []): ?>
            <p class="crud-empty">Nenhum aluno dentro da academia no momento.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Entrada</th>
                            <th>Sequência</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presentes as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['aluno']->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= (new DateTimeImmutable($item['checkin']->entradaEm))->format('H:i') ?></td>
                                <td class="text-dim">🔥 <?= $item['aluno']->streakAtual ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
