<?php

use Academias\Models\Academia;
use Academias\Models\AcademiaCheckin;
use Academias\Models\Aluno;

/**
 * @var array $config
 * @var Academia $academia
 * @var Aluno $aluno
 * @var AcademiaCheckin|null $checkinAberto
 * @var array<int, AcademiaCheckin> $historico
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$slug = htmlspecialchars($academia->slug, ENT_QUOTES, 'UTF-8');
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card" style="max-width: 560px;">
        <div class="hero-eyebrow">Área do aluno</div>
        <h1><?= htmlspecialchars($aluno->nome, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle"><?= htmlspecialchars($academia->nome, ENT_QUOTES, 'UTF-8') ?></p>

        <div class="dash-panel-head" style="margin-top: 1.5rem;">
            <?php if ($checkinAberto !== null): ?>
                <span class="status-badge ok">Você está na academia desde <?= (new DateTimeImmutable($checkinAberto->entradaEm))->format('H:i') ?></span>
            <?php else: ?>
                <span class="status-badge dim">Fora da academia</span>
            <?php endif; ?>
        </div>
        <p class="form-field-hint">Escaneie o QR Code fixo na entrada da academia pra bater check-in e check-out - não existe botão manual aqui, é a prova de que você está no local.</p>

        <div class="kpi-grid" style="margin-top: 1.25rem;">
            <div class="glass-card kpi-card">
                <p class="kpi-label">Sequência atual</p>
                <p class="kpi-valor">🔥 <?= $aluno->streakAtual ?></p>
            </div>
            <div class="glass-card kpi-card">
                <p class="kpi-label">Recorde</p>
                <p class="kpi-valor">🏆 <?= $aluno->streakRecorde ?></p>
            </div>
            <div class="glass-card kpi-card">
                <p class="kpi-label">Pontos</p>
                <p class="kpi-valor"><?= $aluno->pontosFrequencia ?></p>
            </div>
        </div>

        <h3 style="font-size: 0.95rem; margin: 1.75rem 0 1rem;">Histórico recente</h3>
        <?php if ($historico === []): ?>
            <p class="crud-empty">Nenhum check-in ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Entrada</th>
                            <th>Saída</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $checkin): ?>
                            <tr>
                                <td><?= (new DateTimeImmutable($checkin->entradaEm))->format('d/m/Y') ?></td>
                                <td class="text-dim"><?= (new DateTimeImmutable($checkin->entradaEm))->format('H:i') ?></td>
                                <td class="text-dim"><?= $checkin->saidaEm !== null ? (new DateTimeImmutable($checkin->saidaEm))->format('H:i') : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/minha-conta/<?= $slug ?>/sair" style="margin-top: 1.5rem;">
            <?= $csrf ?>
            <button type="submit" class="btn-k btn-k-outline" style="width:100%;">Sair</button>
        </form>
    </div>
</div>
