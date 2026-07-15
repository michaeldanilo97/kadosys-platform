<?php

use Igrejas\Models\KidsConteudo;

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var string $tipo
 * @var array<int, KidsConteudo> $conteudos
 */
$basePath = $config['base_path'] ?? '';
$tipoInfo = KidsConteudo::TIPOS[$tipo];
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <h1><?= $tipoInfo['emoji'] ?> <?= htmlspecialchars($tipoInfo['label'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= count($conteudos) ?> conteúdo<?= count($conteudos) === 1 ? '' : 's' ?> para explorar.</p>
        </div>
        <a href="<?= $basePath ?>/kids" class="kids-voltar"><i class="bi bi-arrow-left"></i> Início</a>
    </div>

    <?php if ($conteudos === []): ?>
        <div class="kids-vazio">Nenhum conteúdo publicado neste tipo ainda.</div>
    <?php else: ?>
        <div class="kids-conteudo-grade">
            <?php foreach ($conteudos as $conteudo): ?>
                <a href="<?= $basePath ?>/kids/conteudo/<?= $conteudo->id ?>" class="kids-conteudo-card">
                    <div class="capa">
                        <?php if ($conteudo->capaPath !== null): ?>
                            <img src="<?= $basePath ?>/<?= htmlspecialchars($conteudo->capaPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                        <?php else: ?>
                            <?= $tipoInfo['emoji'] ?>
                        <?php endif; ?>
                    </div>
                    <div class="corpo">
                        <div class="titulo"><?= htmlspecialchars($conteudo->titulo, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="meta">
                            <?php if ($conteudo->origem === 'kadosys'): ?>
                                <span class="kids-badge-origem kadosys">⭐ KADOSYS</span>
                            <?php else: ?>
                                <span class="kids-badge-origem igreja">🏠 Igreja</span>
                            <?php endif; ?>
                            <span class="kids-badge-recompensa">+<?= $conteudo->xpRecompensa ?> XP</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
