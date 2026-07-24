<?php

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var \Igrejas\Models\BibliaLivro $livro
 * @var int $capitulo
 * @var array<int, \Igrejas\Models\BibliaVersiculo> $versiculos
 * @var bool $ganhouBonus
 * @var array{livro_id: int, capitulo: int, versiculo: int}|null $anterior
 * @var array{livro_id: int, capitulo: int, versiculo: int}|null $proximo
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <p style="font-weight:700;">📖 <?= htmlspecialchars($livro->nome, ENT_QUOTES, 'UTF-8') ?> <?= $capitulo ?></p>
        </div>
        <a href="<?= $basePath ?>/kids/biblia/<?= $livro->id ?>" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <?php if ($ganhouBonus): ?>
        <div class="kids-premio-banner">
            <span class="emoji">📖</span>
            <span>Novo capítulo lido! +3 XP</span>
        </div>
    <?php endif; ?>

    <div class="kids-conteudo-painel">
        <?php if ($versiculos === []): ?>
            <div class="kids-vazio">
                O texto deste capítulo ainda não foi importado. Peça pra
                equipe rodar o importador da Bíblia!
            </div>
        <?php else: ?>
            <div class="kids-biblia-texto">
                <?php foreach ($versiculos as $versiculo): ?>
                    <p><sup class="kids-biblia-versiculo-numero"><?= $versiculo->versiculo ?></sup> <?= htmlspecialchars($versiculo->texto, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="kids-biblia-nav">
            <?php if ($anterior !== null): ?>
                <a href="<?= $basePath ?>/kids/biblia/<?= $anterior['livro_id'] ?>/<?= $anterior['capitulo'] ?>" class="kids-btn-concluir" style="background: linear-gradient(135deg, var(--kids-azul), #2E9FC7); box-shadow: 0 5px 0 #1E7A9C;">
                    <i class="bi bi-arrow-left-circle-fill"></i> Anterior
                </a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <?php if ($proximo !== null): ?>
                <a href="<?= $basePath ?>/kids/biblia/<?= $proximo['livro_id'] ?>/<?= $proximo['capitulo'] ?>" class="kids-btn-concluir">
                    Próximo <i class="bi bi-arrow-right-circle-fill"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
