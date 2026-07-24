<?php

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var string $slug
 * @var array{nome: string, emoji: string, descricao: string, top: int, left: int} $local
 * @var bool $ganhouBonus
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <p style="font-weight:700;">🗺️ Mapa Bíblico</p>
        </div>
        <a href="<?= $basePath ?>/kids/mapa" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <?php if ($ganhouBonus): ?>
        <div class="kids-premio-banner">
            <span class="emoji"><?= $local['emoji'] ?></span>
            <span>Novo lugar explorado! +4 XP</span>
        </div>
    <?php endif; ?>

    <div class="kids-conteudo-painel">
        <div class="capa-grande"><?= $local['emoji'] ?></div>
        <h1><?= htmlspecialchars($local['nome'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p style="line-height: 1.7; font-size: 1rem; color: var(--kids-texto);"><?= htmlspecialchars($local['descricao'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</div>
