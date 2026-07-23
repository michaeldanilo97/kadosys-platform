<?php

use Academias\Models\Academia;
use Academias\Models\Aluno;
use Academias\Models\AvaliacaoFisica;

/**
 * @var array $config
 * @var Academia $academia
 * @var Aluno $aluno
 * @var array<int, AvaliacaoFisica> $historico
 */
$basePath = $config['base_path'] ?? '';
$slug = htmlspecialchars($academia->slug, ENT_QUOTES, 'UTF-8');

/**
 * Grafico de evolucao em SVG puro (mesmo padrao do grafico de carga da
 * ficha de treino) - uma linha simples ligando os valores ao longo do
 * tempo.
 *
 * @param array<int, float> $valores ja em ordem cronologica
 */
$renderEvolucaoSvg = static function (array $valores, string $sufixo): string {
    if (count($valores) < 2) {
        return '<p class="form-field-hint">Registre pelo menos 2 avaliações pra ver a evolução aqui.</p>';
    }

    $largura = 260;
    $altura = 60;
    $margem = 8;

    $min = min($valores);
    $max = max($valores);
    $intervalo = max(0.001, $max - $min);

    $n = count($valores);
    $passo = $n > 1 ? ($largura - 2 * $margem) / ($n - 1) : 0;

    $coordenadas = [];
    foreach ($valores as $i => $valor) {
        $x = round($margem + $i * $passo, 1);
        $y = round($altura - $margem - (($valor - $min) / $intervalo) * ($altura - 2 * $margem), 1);
        $coordenadas[] = ['x' => $x, 'y' => $y];
    }

    $pontosAtributo = implode(' ', array_map(static fn (array $p): string => $p['x'] . ',' . $p['y'], $coordenadas));
    $ultimo = end($coordenadas);

    $svg = '<svg viewBox="0 0 ' . $largura . ' ' . $altura . '" class="treino-evolucao-svg" role="img" aria-label="Gráfico de evolução">'
        . '<polyline points="' . htmlspecialchars($pontosAtributo, ENT_QUOTES, 'UTF-8') . '" fill="none" stroke="var(--primary, #7B5CFA)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />'
        . '<circle cx="' . $ultimo['x'] . '" cy="' . $ultimo['y'] . '" r="3.5" fill="var(--primary, #7B5CFA)" />'
        . '</svg>';

    return $svg . '<p class="form-field-hint">De ' . number_format($min, 1, ',', '.') . ' a ' . number_format($max, 1, ',', '.') . $sufixo . ' nos últimos ' . $n . ' registros.</p>';
};

$pesos = array_map(static fn (AvaliacaoFisica $a): float => $a->pesoKg, $historico);
$gorduras = array_values(array_map(
    static fn (AvaliacaoFisica $a): float => $a->percentualGordura,
    array_filter($historico, static fn (AvaliacaoFisica $a): bool => $a->percentualGordura !== null),
));
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card" style="max-width: 560px;">
        <div class="hero-eyebrow">Minha avaliação física</div>
        <h1><?= htmlspecialchars($aluno->nome, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle">Acompanhe a sua evolução ao longo do tempo.</p>

        <?php if ($historico === []): ?>
            <p class="crud-empty" style="margin-top:1.25rem;">Nenhuma avaliação física registrada ainda. Fale com o seu professor.</p>
        <?php else: ?>
            <h3 style="font-size: 0.95rem; margin: 1.5rem 0 0.5rem;">⚖️ Peso</h3>
            <?= $renderEvolucaoSvg($pesos, ' kg') ?>

            <?php if ($gorduras !== []): ?>
                <h3 style="font-size: 0.95rem; margin: 1.5rem 0 0.5rem;">📊 Percentual de gordura</h3>
                <?= $renderEvolucaoSvg($gorduras, '%') ?>
            <?php endif; ?>

            <h3 style="font-size: 0.95rem; margin: 1.75rem 0 1rem;">Histórico</h3>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Peso</th>
                            <th>% Gordura</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($historico) as $avaliacao): ?>
                            <tr>
                                <td><?= (new DateTimeImmutable($avaliacao->dataAvaliacao))->format('d/m/Y') ?></td>
                                <td class="text-dim"><?= number_format($avaliacao->pesoKg, 1, ',', '.') ?> kg</td>
                                <td class="text-dim"><?= $avaliacao->percentualGordura !== null ? number_format($avaliacao->percentualGordura, 1, ',', '.') . '%' : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <a href="<?= $basePath ?>/minha-conta/<?= $slug ?>" class="btn-k btn-k-outline" style="width:100%; margin-top: 1.5rem;">Voltar</a>
    </div>
</div>
