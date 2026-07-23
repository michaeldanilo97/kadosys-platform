<?php

use Academias\Models\Academia;
use Academias\Models\Aluno;
use Academias\Models\FichaExercicio;
use Academias\Models\FichaTreino;
use Academias\Models\TreinoExecucao;

/**
 * @var array $config
 * @var Academia $academia
 * @var Aluno $aluno
 * @var FichaTreino $ficha
 * @var array<int, FichaExercicio> $exercicios
 * @var array<int, array{hoje: TreinoExecucao|null, evolucao: array<int, TreinoExecucao>}> $execucoes
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$slug = htmlspecialchars($academia->slug, ENT_QUOTES, 'UTF-8');

/**
 * Grafico de evolucao de carga em SVG puro (sem lib externa) - uma
 * linha simples ligando os ultimos registros de carga usada.
 *
 * @param array<int, TreinoExecucao> $execucoes ja em ordem cronologica
 */
$renderEvolucaoSvg = static function (array $execucoes): string {
    $pontos = array_values(array_filter($execucoes, static fn (TreinoExecucao $e): bool => $e->cargaUsadaKg !== null));

    if (count($pontos) < 2) {
        return '<p class="form-field-hint">Registre a carga em pelo menos 2 dias diferentes pra ver a evolução aqui.</p>';
    }

    $largura = 260;
    $altura = 60;
    $margem = 8;

    $cargas = array_map(static fn (TreinoExecucao $e): float => $e->cargaUsadaKg, $pontos);
    $min = min($cargas);
    $max = max($cargas);
    $intervalo = max(0.001, $max - $min);

    $n = count($pontos);
    $passo = $n > 1 ? ($largura - 2 * $margem) / ($n - 1) : 0;

    $coordenadas = [];
    foreach ($pontos as $i => $execucao) {
        $x = round($margem + $i * $passo, 1);
        $y = round($altura - $margem - (($execucao->cargaUsadaKg - $min) / $intervalo) * ($altura - 2 * $margem), 1);
        $coordenadas[] = ['x' => $x, 'y' => $y];
    }

    $pontosAtributo = implode(' ', array_map(static fn (array $p): string => $p['x'] . ',' . $p['y'], $coordenadas));
    $ultimo = end($coordenadas);

    $svg = '<svg viewBox="0 0 ' . $largura . ' ' . $altura . '" class="treino-evolucao-svg" role="img" aria-label="Evolução de carga">'
        . '<polyline points="' . htmlspecialchars($pontosAtributo, ENT_QUOTES, 'UTF-8') . '" fill="none" stroke="var(--primary, #7B5CFA)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />'
        . '<circle cx="' . $ultimo['x'] . '" cy="' . $ultimo['y'] . '" r="3.5" fill="var(--primary, #7B5CFA)" />'
        . '</svg>';

    return $svg . '<p class="form-field-hint">De ' . number_format($min, 1, ',', '.') . ' a ' . number_format($max, 1, ',', '.') . ' kg nos últimos ' . $n . ' registros.</p>';
};
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card" style="max-width: 640px;">
        <div class="hero-eyebrow">Meu treino</div>
        <h1><?= htmlspecialchars($ficha->nome, ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if ($ficha->objetivo): ?>
            <p class="subtitle"><?= htmlspecialchars($ficha->objetivo, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($exercicios === []): ?>
            <p class="crud-empty">Essa ficha ainda não tem exercícios cadastrados.</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:1.25rem; margin-top:1.25rem;">
                <?php foreach ($exercicios as $exercicio): ?>
                    <?php $hoje = $execucoes[$exercicio->id]['hoje'] ?? null; ?>
                    <div class="glass-card dash-panel" style="padding:1.1rem 1.25rem;">
                        <div class="dash-panel-head">
                            <h3 style="margin:0; font-size:0.95rem;"><?= htmlspecialchars($exercicio->nomeExercicio, ENT_QUOTES, 'UTF-8') ?></h3>
                            <?php if ($hoje !== null): ?>
                                <span class="status-badge ok">Feito hoje</span>
                            <?php endif; ?>
                        </div>
                        <p class="form-field-hint" style="margin:0.25rem 0 0.75rem;">
                            <?= $exercicio->series ?? '-' ?>x<?= htmlspecialchars($exercicio->repeticoes ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($exercicio->cargaSugeridaKg !== null): ?>
                                · sugerido <?= number_format($exercicio->cargaSugeridaKg, 1, ',', '.') ?> kg
                            <?php endif; ?>
                            <?php if ($exercicio->grupoMuscular): ?>
                                · <?= htmlspecialchars($exercicio->grupoMuscular, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </p>

                        <?= $renderEvolucaoSvg($execucoes[$exercicio->id]['evolucao'] ?? []) ?>

                        <form method="POST" action="<?= $basePath ?>/minha-conta/<?= $slug ?>/treino/<?= $ficha->id ?>/exercicios/<?= $exercicio->id ?>" style="display:flex; gap:0.6rem; margin-top:0.75rem; flex-wrap:wrap; align-items:end;">
                            <?= $csrf ?>
                            <div class="form-field" style="flex:1; min-width:100px; margin:0;">
                                <label>Carga usada (kg)</label>
                                <input type="text" name="carga_usada_kg" value="<?= $hoje?->cargaUsadaKg !== null ? htmlspecialchars((string) $hoje->cargaUsadaKg, ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="Ex.: 20">
                            </div>
                            <div class="form-field" style="flex:1; min-width:100px; margin:0;">
                                <label>Séries completas</label>
                                <input type="number" name="series_completas" min="0" value="<?= $hoje?->seriesCompletas !== null ? $hoje->seriesCompletas : '' ?>">
                            </div>
                            <button type="submit" class="btn-k btn-k-grad"><?= $hoje !== null ? 'Atualizar' : 'Marcar feito' ?></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="<?= $basePath ?>/minha-conta/<?= $slug ?>/treino" class="btn-k btn-k-outline" style="width:100%; margin-top: 1.5rem;">Voltar</a>
    </div>
</div>
