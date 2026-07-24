<?php

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var \Igrejas\Models\BibliaLivro $livro
 * @var array<int, int> $lidos numeros dos capitulos ja lidos
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <h1><?= htmlspecialchars($livro->nome, ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Toque num capítulo pra começar a ler.</p>
        </div>
        <a href="<?= $basePath ?>/kids/biblia" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <div class="kids-capitulo-grade">
        <?php for ($capitulo = 1; $capitulo <= $livro->totalCapitulos; $capitulo++): ?>
            <a href="<?= $basePath ?>/kids/biblia/<?= $livro->id ?>/<?= $capitulo ?>" class="kids-capitulo-numero<?= in_array($capitulo, $lidos, true) ? ' lido' : '' ?>">
                <?= $capitulo ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
