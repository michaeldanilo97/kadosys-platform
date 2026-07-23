<?php

use Igrejas\Models\KidsAvatar;

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var array{nivelAtual: int, xpAtual: int, xpInicioNivel: int, xpProximoNivel: ?int, percentual: int} $progresso
 * @var array<int, array{slug: string, emoji: string, nome: string, nivel: ?int, custoMoedas: ?int}> $catalogoChapeus
 * @var array<int, array{slug: string, emoji: string, nome: string, nivel: ?int, custoMoedas: ?int}> $catalogoAcessorios
 * @var array<int, array{slug: string, emoji: string, nome: string, nivel: ?int, custoMoedas: ?int, gradiente: string}> $catalogoFundos
 * @var array<int, array{slug: string, nome: string, nivel: ?int, custoMoedas: ?int}> $catalogoTitulos
 * @var int $nivel
 * @var array<string, array<int, string>> $comprados
 * @var string $csrfToken
 * @var bool|null $salvo
 * @var string|null $compraErro
 * @var string|null $compraOk
 */
$basePath = $config['base_path'] ?? '';

$fundoEquipado = KidsAvatar::encontrar($catalogoFundos, $crianca->avatarFundo) ?? $catalogoFundos[0];
$tituloEquipado = KidsAvatar::encontrar($catalogoTitulos, $crianca->avatarTitulo);
$chapeuEquipado = KidsAvatar::encontrar($catalogoChapeus, $crianca->avatarChapeu);
$acessorioEquipado = KidsAvatar::encontrar($catalogoAcessorios, $crianca->avatarAcessorio);

/**
 * Grade de escolha (radios) dentro do form principal de "Salvar
 * visual" - so mostra itens ja desbloqueados (por nivel ou por compra)
 * ou os bloqueados por nivel. Os itens da loja AINDA NAO comprados nao
 * entram aqui (formulario de compra nao pode ficar aninhado dentro
 * deste <form> - HTML nao permite - ver secao "Loja de moedas"
 * separada, fora do form principal).
 *
 * @param array<int, array<string, mixed>> $catalogo
 * @param array<int, string> $compradosCategoria
 */
$renderGrade = static function (array $catalogo, string $campo, ?string $equipadoSlug, int $nivelCrianca, array $compradosCategoria, ?string $labelNenhum): void {
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
            <?php
            $comprado = in_array($item['slug'], $compradosCategoria, true);
            $desbloqueado = ($item['nivel'] !== null && $item['nivel'] <= $nivelCrianca) || $comprado;
            $naLoja = $item['nivel'] === null;
            ?>
            <?php if ($desbloqueado): ?>
                <label class="kids-item-card<?= $equipadoSlug === $item['slug'] ? ' selecionado' : '' ?>">
                    <input
                        type="radio"
                        name="<?= $campo ?>"
                        value="<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>"
                        <?= $equipadoSlug === $item['slug'] ? 'checked' : '' ?>
                    >
                    <span class="kids-item-emoji"><?= $item['emoji'] ?? '🏅' ?></span>
                    <span class="kids-item-nome"><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($naLoja): ?>
                        <span class="kids-item-selo-loja">🪙 Da loja</span>
                    <?php endif; ?>
                </label>
            <?php elseif ($naLoja): ?>
                <?php // Ainda nao comprado - aparece na secao "Loja de moedas", fora deste form. ?>
            <?php else: ?>
                <label class="kids-item-card bloqueado">
                    <input type="radio" name="<?= $campo ?>" value="<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                    <span class="kids-item-emoji"><?= $item['emoji'] ?? '🏅' ?></span>
                    <span class="kids-item-nome"><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="kids-item-cadeado"><i class="bi bi-lock-fill"></i> Nível <?= $item['nivel'] ?></span>
                </label>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
};

/**
 * Itens da loja (custoMoedas preenchido) ainda nao comprados, de todas
 * as categorias juntas - cada um ganha seu proprio <form> independente
 * (irmao do form principal, nunca aninhado - ver comprar() no
 * controller).
 *
 * @return array<int, array{categoria: string, rotulo: string, item: array<string, mixed>}>
 */
$itensDaLojaNaoComprados = static function () use ($catalogoChapeus, $catalogoAcessorios, $catalogoFundos, $catalogoTitulos, $comprados): array {
    $porCategoria = [
        'chapeu' => ['rotulo' => '🎩 Chapéu', 'catalogo' => $catalogoChapeus],
        'acessorio' => ['rotulo' => '🎒 Acessório', 'catalogo' => $catalogoAcessorios],
        'fundo' => ['rotulo' => '🌈 Fundo', 'catalogo' => $catalogoFundos],
        'titulo' => ['rotulo' => '🏅 Título', 'catalogo' => $catalogoTitulos],
    ];

    $itens = [];

    foreach ($porCategoria as $categoria => $info) {
        foreach ($info['catalogo'] as $item) {
            if ($item['nivel'] === null && !in_array($item['slug'], $comprados[$categoria], true)) {
                $itens[] = ['categoria' => $categoria, 'rotulo' => $info['rotulo'], 'item' => $item];
            }
        }
    }

    return $itens;
};
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <h1>🧑‍🎤 Meu Avatar</h1>
            <p>Ganhe XP participando ou moedas pra desbloquear itens novos!</p>
        </div>
        <span class="kids-stats-mini"><span>🪙 <?= $crianca->moedas ?></span></span>
        <a href="<?= $basePath ?>/kids" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <?php if (!empty($salvo)): ?>
        <div class="kids-premio-banner">
            <span class="emoji">✨</span>
            <span>Visual salvo! Assim que você vai aparecer pro grupo.</span>
        </div>
    <?php endif; ?>

    <?php if ($compraOk): ?>
        <div class="kids-premio-banner">
            <span class="emoji">🪙</span>
            <span>"<?= htmlspecialchars($compraOk, ENT_QUOTES, 'UTF-8') ?>" comprado! Já dá pra equipar aqui embaixo.</span>
        </div>
    <?php endif; ?>

    <?php if ($compraErro): ?>
        <div class="kids-login-erro">
            <span><?= htmlspecialchars($compraErro, ENT_QUOTES, 'UTF-8') ?></span>
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
        <?php $renderGrade($catalogoTitulos, 'avatar_titulo', $crianca->avatarTitulo, $nivel, $comprados['titulo'], 'Sem título'); ?>

        <h2 class="kids-secao-titulo">🎩 Chapéu</h2>
        <?php $renderGrade($catalogoChapeus, 'avatar_chapeu', $crianca->avatarChapeu, $nivel, $comprados['chapeu'], 'Sem chapéu'); ?>

        <h2 class="kids-secao-titulo">🎒 Acessório</h2>
        <?php $renderGrade($catalogoAcessorios, 'avatar_acessorio', $crianca->avatarAcessorio, $nivel, $comprados['acessorio'], 'Sem acessório'); ?>

        <h2 class="kids-secao-titulo">🌈 Fundo</h2>
        <?php $renderGrade($catalogoFundos, 'avatar_fundo', $fundoEquipado['slug'], $nivel, $comprados['fundo'], null); ?>

        <div style="margin-top: 1.6rem;">
            <button type="submit" class="kids-btn-concluir"><i class="bi bi-check-circle-fill"></i> Salvar visual</button>
        </div>
    </form>

    <?php $itensLoja = $itensDaLojaNaoComprados(); ?>
    <?php if ($itensLoja !== []): ?>
        <h2 class="kids-secao-titulo">🪙 Loja de moedas</h2>
        <div class="kids-item-grade">
            <?php foreach ($itensLoja as $entrada): ?>
                <?php $item = $entrada['item']; ?>
                <div class="kids-item-card kids-item-card-loja">
                    <span class="kids-item-emoji"><?= $item['emoji'] ?? '🏅' ?></span>
                    <span class="kids-item-nome"><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="kids-item-selo-loja"><?= htmlspecialchars($entrada['rotulo'], ENT_QUOTES, 'UTF-8') ?></span>
                    <form method="POST" action="<?= $basePath ?>/kids/avatar/comprar" class="kids-item-form-comprar">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="categoria" value="<?= htmlspecialchars($entrada['categoria'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="slug" value="<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="kids-item-btn-comprar" <?= $crianca->moedas < $item['custoMoedas'] ? 'disabled' : '' ?>>
                            🪙 Comprar por <?= $item['custoMoedas'] ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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
