<?php

use Igrejas\Core\View;
use Igrejas\Models\KidsConteudo;

/**
 * @var array $config
 * @var array<string, int> $contagens
 * @var array<int, KidsConteudo> $recentes
 * @var array<int, \Igrejas\Models\KidsCrianca> $criancasAtivas
 * @var \Igrejas\Models\KidsCrianca|null $criancaAtiva
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';

$cores = ['kids-amarelo', 'kids-azul', 'kids-rosa', 'kids-verde', 'kids-roxo', 'kids-laranja', 'kids-vermelho'];
$primeiroNome = $criancaAtiva !== null ? explode(' ', $criancaAtiva->nome)[0] : null;
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/kids-biblioteca.css?v=<?= View::assetVersion('assets/css/kids-biblioteca.css') ?>">

<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <h1><?= $primeiroNome !== null ? 'Oi, ' . htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8') . '! 👋' : 'Bem-vindo à Biblioteca Kids! 🌈' ?></h1>
            <p>Escolha uma aventura para começar hoje.</p>
        </div>
        <a href="<?= $basePath ?>/dashboard/kids" class="kids-voltar"><i class="bi bi-arrow-left"></i> Modo equipe</a>
    </div>

    <div class="kids-seletor">
        <form method="POST" action="<?= $basePath ?>/dashboard/kids/biblioteca/crianca">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <label for="crianca_id" style="font-weight:700;">Quem está navegando?</label>
            <select id="crianca_id" name="crianca_id" onchange="this.form.submit()">
                <option value="">Selecione a criança...</option>
                <?php foreach ($criancasAtivas as $crianca): ?>
                    <option value="<?= $crianca->id ?>" <?= $criancaAtiva?->id === $crianca->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit">Confirmar</button></noscript>
        </form>

        <?php if ($criancaAtiva !== null): ?>
            <span class="kids-crianca-ativa-chip">
                <span class="avatar">
                    <?php if ($criancaAtiva->fotoPath !== null): ?>
                        <img src="<?= $basePath ?>/<?= htmlspecialchars($criancaAtiva->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($criancaAtiva->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </span>
                <?= htmlspecialchars($criancaAtiva->nome, ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span class="kids-stats-mini">
                <span>⭐ <?= $criancaAtiva->xp ?> XP</span>
                <span>🪙 <?= $criancaAtiva->moedas ?></span>
                <span>🔥 <?= $criancaAtiva->sequenciaDias ?></span>
            </span>
        <?php endif; ?>
    </div>

    <div class="kids-tipo-grade">
        <?php $i = 0; foreach (KidsConteudo::TIPOS as $slug => $info): ?>
            <?php if ($contagens[$slug] === 0) continue; ?>
            <a href="<?= $basePath ?>/dashboard/kids/biblioteca/tipo/<?= $slug ?>" class="kids-tipo-card" style="--cor-cartao: var(--<?= $cores[$i % count($cores)] ?>);">
                <span class="emoji"><?= $info['emoji'] ?></span>
                <span class="nome"><?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="contagem"><?= $contagens[$slug] ?> conteúdo<?= $contagens[$slug] === 1 ? '' : 's' ?></span>
            </a>
            <?php $i++; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($recentes !== []): ?>
        <h2 class="kids-secao-titulo">✨ Novidades</h2>
        <div class="kids-conteudo-grade">
            <?php foreach ($recentes as $conteudo): ?>
                <?php $tipoInfo = $conteudo->tipoInfo(); ?>
                <a href="<?= $basePath ?>/dashboard/kids/biblioteca/conteudo/<?= $conteudo->id ?>" class="kids-conteudo-card">
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

    <?php if (array_sum($contagens) === 0): ?>
        <div class="kids-vazio">
            Ainda não há conteúdos publicados. Peça para a equipe adicionar em <strong>Kids &gt; Conteúdos</strong>.
        </div>
    <?php endif; ?>
</div>
