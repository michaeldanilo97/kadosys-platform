<?php

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var array{top: array<int, array{id: int, nome: string, xp: int, nivel: int, souEu: bool}>, minhaPosicao: int, totalCriancas: int} $rankingIgreja
 * @var array<int, array{nomeIgreja: string, xpTotal: int, souEu: bool}> $rankingEntreIgrejas
 */
$basePath = $config['base_path'] ?? '';
$medalhas = ['🥇', '🥈', '🥉'];
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <h1>🏆 Ranking</h1>
            <p>Veja quem mais brilhou participando da Biblioteca!</p>
        </div>
        <a href="<?= $basePath ?>/kids" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <h2 class="kids-secao-titulo">⭐ Sua igreja</h2>
    <div class="kids-ranking-lista">
        <?php foreach ($rankingIgreja['top'] as $indice => $item): ?>
            <div class="kids-ranking-linha<?= $item['souEu'] ? ' sou-eu' : '' ?>">
                <span class="posicao"><?= $medalhas[$indice] ?? ($indice + 1) . 'º' ?></span>
                <span class="nome"><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?><?= $item['souEu'] ? ' (você)' : '' ?></span>
                <span class="nivel">Nível <?= $item['nivel'] ?></span>
                <span class="xp">⭐ <?= $item['xp'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($rankingIgreja['minhaPosicao'] > count($rankingIgreja['top'])): ?>
        <p class="kids-nivel-legenda">Sua posição: #<?= $rankingIgreja['minhaPosicao'] ?> de <?= $rankingIgreja['totalCriancas'] ?> crianças. Continue participando pra subir! 💪</p>
    <?php endif; ?>

    <?php if ($rankingEntreIgrejas !== []): ?>
        <h2 class="kids-secao-titulo">⛪ Entre igrejas KADOSYS</h2>
        <div class="kids-ranking-lista">
            <?php foreach ($rankingEntreIgrejas as $indice => $igreja): ?>
                <div class="kids-ranking-linha<?= $igreja['souEu'] ? ' sou-eu' : '' ?>">
                    <span class="posicao"><?= $medalhas[$indice] ?? ($indice + 1) . 'º' ?></span>
                    <span class="nome"><?= htmlspecialchars($igreja['nomeIgreja'], ENT_QUOTES, 'UTF-8') ?><?= $igreja['souEu'] ? ' (sua igreja)' : '' ?></span>
                    <span class="xp">⭐ <?= $igreja['xpTotal'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
