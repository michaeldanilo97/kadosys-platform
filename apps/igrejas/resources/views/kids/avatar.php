<?php

use Igrejas\Models\KidsAvatar;

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var array{nivelAtual: int, xpAtual: int, xpInicioNivel: int, xpProximoNivel: ?int, percentual: int} $progresso
 * @var array<int, array{slug: string, emoji: string, nome: string, nivel: int}> $catalogoChapeus
 * @var array<int, array{slug: string, emoji: string, nome: string, nivel: int}> $catalogoAcessorios
 * @var array<int, array{slug: string, emoji: string, nome: string, nivel: int, gradiente: string}> $catalogoFundos
 * @var array<int, array{slug: string, nome: string, nivel: int}> $catalogoTitulos
 * @var int $nivel
 * @var string $csrfToken
 * @var bool|null $salvo
 */
$basePath = $config['base_path'] ?? '';

$fundoEquipado = KidsAvatar::encontrar($catalogoFundos, $crianca->avatarFundo) ?? $catalogoFundos[0];
$tituloEquipado = KidsAvatar::encontrar($catalogoTitulos, $crianca->avatarTitulo);
$chapeuEquipado = KidsAvatar::encontrar($catalogoChapeus, $crianca->avatarChapeu);
$acessorioEquipado = KidsAvatar::encontrar($catalogoAcessorios, $crianca->avatarAcessorio);

/**
 * @param array<int, array<string, mixed>> $catalogo
 */
$renderGrade = static function (array $catalogo, string $campo, ?string $equipadoSlug, int $nivelCrianca, ?string $labelNenhum) use ($basePath): void {
    ?>
    <div class="kids-item-grade">
        <?php if ($labelNenhum !== null): ?>
            <label class="kids-item-card<?= $equipadoSlug === null ? ' selecionado' : '' ?>">
                <input type="radio" name="<?= $campo ?>" value="" <?= $equipadoSlug === null ? 'checked' : '' ?>>
                <span class="kids-item-emoji">🚫</span>
                <span class="kids-item-nome"><?= htmlspecialchars($labelNenhum, ENT_QUOTES, 'UTF-8') ?></span>
            </label>
        <?php endif; ?>
        <?php foreach ($catalogo as $item): ?>
            <?php $desbloqueado = $item['nivel'] <= $nivelCrianca; ?>
            <label class="kids-item-card<?= $equipadoSlug === $item['slug'] ? ' selecionado' : ''
                ?><?= $desbloqueado ? '' : ' bloqueado' ?>">
                <input
                    type="radio"
                    name="<?= $campo ?>"
                    value="<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>"
                    <?= $equipadoSlug === $item['slug'] ? 'checked' : '' ?>
                    <?= $desbloqueado ? '' : 'disabled' ?>
                >
                <span class="kids-item-emoji"><?= $item['emoji'] ?? '🏅' ?></span>
                <span class="kids-item-nome"><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!$desbloqueado): ?>
                    <span class="kids-item-cadeado"><i class="bi bi-lock-fill"></i> Nível <?= $item['nivel'] ?></span>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
    </div>
    <?php
};
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <h1>🧑‍🎤 Meu Avatar</h1>
            <p>Ganhe XP participando pra desbloquear itens novos!</p>
        </div>
        <a href="<?= $basePath ?>/kids" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <?php if (!empty($salvo)): ?>
        <div class="kids-premio-banner">
            <span class="emoji">✨</span>
            <span>Visual salvo! Assim que você vai aparecer pro grupo.</span>
        </div>
    <?php endif; ?>

    <div class="kids-avatar-stage" style="background: <?= $fundoEquipado['gradiente'] ?>;">
        <div class="kids-avatar-figura">
            <?php if ($chapeuEquipado !== null): ?>
                <span class="kids-avatar-chapeu"><?= $chapeuEquipado['emoji'] ?></span>
            <?php endif; ?>
            <span class="kids-avatar-boneco">
                <?php if ($crianca->fotoPath !== null): ?>
                    <img src="<?= $basePath ?>/<?= htmlspecialchars($crianca->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                <?php else: ?>
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($crianca->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </span>
            <?php if ($acessorioEquipado !== null): ?>
                <span class="kids-avatar-acessorio"><?= $acessorioEquipado['emoji'] ?></span>
            <?php endif; ?>
        </div>
        <span class="kids-avatar-nome"><?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?></span>
        <?php if ($tituloEquipado !== null): ?>
            <span class="kids-avatar-titulo-chip"><?= htmlspecialchars($tituloEquipado['nome'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>

    <div class="kids-nivel-card">
        <div class="kids-nivel-cabecalho">
            <span class="kids-nivel-selo">Nível <?= $progresso['nivelAtual'] ?></span>
            <?php if ($progresso['xpProximoNivel'] !== null): ?>
                <span class="kids-nivel-info"><?= $progresso['xpAtual'] ?> / <?= $progresso['xpProximoNivel'] ?> XP</span>
            <?php else: ?>
                <span class="kids-nivel-info">Nível máximo! 🏆</span>
            <?php endif; ?>
        </div>
        <div class="kids-nivel-barra">
            <div class="kids-nivel-barra-preenchida" style="width: <?= $progresso['percentual'] ?>%;"></div>
        </div>
        <?php if ($progresso['xpProximoNivel'] !== null): ?>
            <p class="kids-nivel-legenda">Faltam <?= $progresso['xpProximoNivel'] - $progresso['xpAtual'] ?> XP pro próximo nível.</p>
        <?php endif; ?>
    </div>

    <form method="POST" action="<?= $basePath ?>/kids/avatar">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <h2 class="kids-secao-titulo">🏅 Título</h2>
        <?php $renderGrade($catalogoTitulos, 'avatar_titulo', $crianca->avatarTitulo, $nivel, 'Sem título'); ?>

        <h2 class="kids-secao-titulo">🎩 Chapéu</h2>
        <?php $renderGrade($catalogoChapeus, 'avatar_chapeu', $crianca->avatarChapeu, $nivel, 'Sem chapéu'); ?>

        <h2 class="kids-secao-titulo">🎒 Acessório</h2>
        <?php $renderGrade($catalogoAcessorios, 'avatar_acessorio', $crianca->avatarAcessorio, $nivel, 'Sem acessório'); ?>

        <h2 class="kids-secao-titulo">🌈 Fundo</h2>
        <?php $renderGrade($catalogoFundos, 'avatar_fundo', $fundoEquipado['slug'], $nivel, null); ?>

        <div style="margin-top: 1.6rem;">
            <button type="submit" class="kids-btn-concluir"><i class="bi bi-check-circle-fill"></i> Salvar visual</button>
        </div>
    </form>
</div>

<script>
    (function () {
        // Preve visualiza o boné/acessório/fundo/título assim que a
        // criança clica numa opção, sem esperar salvar+recarregar - o
        // formulário continua so gravando no banco quando "Salvar
        // visual" e enviado.
        var catalogos = {
            avatar_chapeu: <?= json_encode($catalogoChapeus, JSON_UNESCAPED_UNICODE) ?>,
            avatar_acessorio: <?= json_encode($catalogoAcessorios, JSON_UNESCAPED_UNICODE) ?>,
            avatar_fundo: <?= json_encode($catalogoFundos, JSON_UNESCAPED_UNICODE) ?>,
            avatar_titulo: <?= json_encode($catalogoTitulos, JSON_UNESCAPED_UNICODE) ?>
        };

        var estagio = document.querySelector('.kids-avatar-stage');
        var figura = document.querySelector('.kids-avatar-figura');

        function itemDoCatalogo(campo, slug) {
            if (!slug) {
                return null;
            }
            return catalogos[campo].find(function (item) { return item.slug === slug; }) || null;
        }

        function atualizarEmoji(seletor, classe, emoji, primeiro) {
            var atual = figura.querySelector(seletor);
            if (!emoji) {
                if (atual) { atual.remove(); }
                return;
            }
            if (!atual) {
                atual = document.createElement('span');
                atual.className = classe;
                if (primeiro) {
                    figura.prepend(atual);
                } else {
                    figura.appendChild(atual);
                }
            }
            atual.textContent = emoji;
        }

        document.querySelectorAll('input[name="avatar_chapeu"]').forEach(function (input) {
            input.addEventListener('change', function () {
                var escolhido = itemDoCatalogo('avatar_chapeu', input.value);
                atualizarEmoji('.kids-avatar-chapeu', 'kids-avatar-chapeu', escolhido ? escolhido.emoji : null, true);
            });
        });

        document.querySelectorAll('input[name="avatar_acessorio"]').forEach(function (input) {
            input.addEventListener('change', function () {
                var escolhido = itemDoCatalogo('avatar_acessorio', input.value);
                atualizarEmoji('.kids-avatar-acessorio', 'kids-avatar-acessorio', escolhido ? escolhido.emoji : null, false);
            });
        });

        document.querySelectorAll('input[name="avatar_fundo"]').forEach(function (input) {
            input.addEventListener('change', function () {
                var escolhido = itemDoCatalogo('avatar_fundo', input.value);
                if (escolhido && estagio) {
                    estagio.style.background = escolhido.gradiente;
                }
            });
        });

        document.querySelectorAll('input[name="avatar_titulo"]').forEach(function (input) {
            input.addEventListener('change', function () {
                var escolhido = itemDoCatalogo('avatar_titulo', input.value);
                var chip = estagio.querySelector('.kids-avatar-titulo-chip');
                if (!escolhido) {
                    if (chip) { chip.remove(); }
                    return;
                }
                if (!chip) {
                    chip = document.createElement('span');
                    chip.className = 'kids-avatar-titulo-chip';
                    estagio.appendChild(chip);
                }
                chip.textContent = escolhido.nome;
            });
        });
    })();
</script>
