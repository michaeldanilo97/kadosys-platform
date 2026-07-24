<?php

use Igrejas\Models\KidsConteudo;

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var array<string, int> $contagens
 * @var array<int, KidsConteudo> $recentes
 * @var array<int, array{conteudoId: int, titulo: string, tipo: string, emoji: string, concluida: bool}> $missoes
 * @var array{sequencia: int, xp: int, moedas: int}|null $acessoApp
 * @var array<int, array{slug: string, nome: string, emoji: string}> $novosEmblemas
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
$cores = ['kids-amarelo', 'kids-azul', 'kids-rosa', 'kids-verde', 'kids-roxo', 'kids-laranja', 'kids-vermelho'];
$primeiroNome = explode(' ', $crianca->nome)[0];
?>
<div class="kids-mundo">
    <div class="kids-app-topo">
        <div class="kids-app-perfil">
            <span class="avatar">
                <?php if ($crianca->fotoPath !== null): ?>
                    <img src="<?= $basePath ?>/<?= htmlspecialchars($crianca->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                <?php else: ?>
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($crianca->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </span>
            <span>
                Oi, <?= htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8') ?>! 👋
                <span class="kids-stats-mini" style="margin-top: 0.2rem;">
                    <span>⭐ <?= $crianca->xp ?></span>
                    <span>🪙 <?= $crianca->moedas ?></span>
                    <span title="Sequência na igreja">🔥 <?= $crianca->sequenciaDias ?></span>
                    <span title="Sequência acessando a Biblioteca">🗓️ <?= $crianca->sequenciaAppDias ?></span>
                </span>
            </span>
        </div>
        <div style="display: flex; gap: 0.6rem;">
            <a href="<?= $basePath ?>/kids/duelos" class="kids-app-sair" style="background: linear-gradient(135deg, var(--kids-azul), var(--kids-roxo)); color: #FFFFFF;">
                <i class="bi bi-controller"></i> Jogar com amigo<span class="kids-duelo-nav-badge" data-duelo-badge hidden>0</span>
            </a>
            <a href="<?= $basePath ?>/kids/ranking" class="kids-app-sair" style="background: linear-gradient(135deg, var(--kids-amarelo), var(--kids-laranja));">
                <i class="bi bi-trophy-fill"></i> Ranking
            </a>
            <a href="<?= $basePath ?>/kids/avatar" class="kids-app-sair" style="background: linear-gradient(135deg, var(--kids-roxo), var(--kids-rosa)); color: #FFFFFF;">
                <i class="bi bi-person-video3"></i> Meu Avatar
            </a>
            <a href="<?= $basePath ?>/kids/emblemas" class="kids-app-sair" style="background: linear-gradient(135deg, var(--kids-verde), var(--kids-azul)); color: #FFFFFF;">
                <i class="bi bi-award-fill"></i> Emblemas
            </a>
            <a href="<?= $basePath ?>/kids/biblia" class="kids-app-sair" style="background: linear-gradient(135deg, #4A6FA5, var(--kids-azul)); color: #FFFFFF;">
                <i class="bi bi-book-fill"></i> Bíblia
            </a>
            <a href="<?= $basePath ?>/kids/mapa" class="kids-app-sair" style="background: linear-gradient(135deg, var(--kids-laranja), var(--kids-vermelho)); color: #FFFFFF;">
                <i class="bi bi-geo-alt-fill"></i> Mapa
            </a>
            <form method="POST" action="<?= $basePath ?>/kids/sair">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="kids-app-sair"><i class="bi bi-box-arrow-right"></i> Sair</button>
            </form>
        </div>
    </div>

    <?php if ($acessoApp !== null && ($acessoApp['xp'] > 0 || $acessoApp['moedas'] > 0)): ?>
        <div class="kids-premio-banner">
            <span class="emoji">🔥</span>
            <span>Sequência de <?= $acessoApp['sequencia'] ?> dias na Biblioteca! Bônus de +<?= $acessoApp['xp'] ?> XP e +<?= $acessoApp['moedas'] ?> moedas!</span>
        </div>
    <?php endif; ?>

    <?php foreach ($novosEmblemas as $novoEmblema): ?>
        <div class="kids-premio-banner">
            <span class="emoji"><?= $novoEmblema['emoji'] ?></span>
            <span>Novo emblema: <strong><?= htmlspecialchars($novoEmblema['nome'], ENT_QUOTES, 'UTF-8') ?></strong>! Veja em Emblemas 🏅</span>
        </div>
    <?php endforeach; ?>

    <?php if ($missoes !== []): ?>
        <h2 class="kids-secao-titulo">🎯 Missão do dia</h2>
        <div class="kids-missao-lista">
            <?php foreach ($missoes as $missao): ?>
                <a href="<?= $basePath ?>/kids/conteudo/<?= $missao['conteudoId'] ?>" class="kids-missao-card<?= $missao['concluida'] ? ' concluida' : '' ?>">
                    <span class="emoji"><?= $missao['emoji'] ?></span>
                    <span class="titulo"><?= htmlspecialchars($missao['titulo'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($missao['concluida']): ?>
                        <span class="kids-missao-selo"><i class="bi bi-check-circle-fill"></i> Feito!</span>
                    <?php else: ?>
                        <span class="kids-missao-selo pendente">+8 🪙 de bônus</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p class="kids-login-subtitulo" style="text-align: left; margin-bottom: 1.5rem;">Escolha uma aventura para começar hoje.</p>

    <div class="kids-tipo-grade">
        <?php $i = 0; foreach (KidsConteudo::TIPOS as $slug => $info): ?>
            <?php if ($contagens[$slug] === 0) continue; ?>
            <a href="<?= $basePath ?>/kids/tipo/<?= $slug ?>" class="kids-tipo-card" style="--cor-cartao: var(--<?= $cores[$i % count($cores)] ?>);">
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

    <?php if (array_sum($contagens) === 0): ?>
        <div class="kids-vazio">
            Ainda não há conteúdos publicados. Peça para a equipe adicionar em Kids &gt; Conteúdos.
        </div>
    <?php endif; ?>
</div>

<script>
    (function () {
        var basePath = <?= json_encode($basePath, JSON_UNESCAPED_SLASHES) ?>;
        var badge = document.querySelector('[data-duelo-badge]');

        if (!badge) {
            return;
        }

        function consultarPendentes() {
            fetch(basePath + '/kids/duelos/pendentes')
                .then(function (resposta) { return resposta.json(); })
                .then(function (dados) {
                    if (dados && dados.total > 0) {
                        badge.textContent = String(dados.total);
                        badge.hidden = false;
                    } else {
                        badge.hidden = true;
                    }
                })
                .catch(function () {});
        }

        consultarPendentes();
        setInterval(consultarPendentes, 8000);
    })();
</script>
