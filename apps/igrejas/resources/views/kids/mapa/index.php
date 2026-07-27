<?php

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var array<string, array{nome: string, emoji: string, descricao: string, top: int, left: int}> $locais
 * @var array<int, string> $explorados
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <h1>🗺️ Mapa Bíblico</h1>
            <p>Toque nos lugares pra descobrir o que aconteceu ali! Você já explorou <?= count($explorados) ?> de <?= count($locais) ?>.</p>
        </div>
        <a href="<?= $basePath ?>/kids" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <div class="kids-mapa-area">
        <?php foreach ($locais as $slug => $local): ?>
            <a
                href="<?= $basePath ?>/kids/mapa/<?= $slug ?>"
                class="kids-mapa-pin<?= in_array($slug, $explorados, true) ? ' explorado' : '' ?>"
                style="top: <?= $local['top'] ?>%; left: <?= $local['left'] ?>%;"
            >
                <span class="kids-mapa-pin-emoji"><?= $local['emoji'] ?></span>
                <span class="kids-mapa-pin-nome"><?= htmlspecialchars($local['nome'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
