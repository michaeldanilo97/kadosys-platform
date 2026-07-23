<?php

use Academias\Models\Academia;
use Academias\Models\Aluno;

/**
 * @var array $config
 * @var Academia $academia
 * @var Aluno $aluno
 * @var string $tipo 'entrada' ou 'saida'
 * @var DateTimeImmutable $horario
 * @var int|null $duracaoMinutos
 */
$basePath = $config['base_path'] ?? '';
$slug = htmlspecialchars($academia->slug, ENT_QUOTES, 'UTF-8');
$ehEntrada = $tipo === 'entrada';
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card" style="text-align: center;">
        <div style="font-size: 3.5rem; margin-bottom: 0.5rem;"><?= $ehEntrada ? '✅' : '👋' ?></div>
        <h1><?= $ehEntrada ? 'Check-in feito!' : 'Até a próxima!' ?></h1>
        <p class="subtitle">
            <?php if ($ehEntrada): ?>
                Entrada registrada às <?= $horario->format('H:i') ?>. Bom treino, <?= htmlspecialchars($aluno->nome, ENT_QUOTES, 'UTF-8') ?>!
            <?php else: ?>
                Check-out registrado às <?= $horario->format('H:i') ?><?php if (($duracaoMinutos ?? 0) > 0): ?> - treino de <?= intdiv($duracaoMinutos, 60) ?>h<?= str_pad((string) ($duracaoMinutos % 60), 2, '0', STR_PAD_LEFT) ?>min<?php endif; ?>.
            <?php endif; ?>
        </p>

        <?php if ($ehEntrada && $aluno->streakAtual > 1): ?>
            <div class="status-badge ai" style="margin-top: 1rem;">🔥 Sequência de <?= $aluno->streakAtual ?> dias</div>
        <?php endif; ?>

        <a href="<?= $basePath ?>/minha-conta/<?= $slug ?>" class="btn-k btn-k-grad" style="width:100%; margin-top: 1.5rem;">Ver minha conta</a>
    </div>
</div>
