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
 * @var array<int, array{slug: string, nome: string, nivel: ?int, custoMoedas: ?int, cor: string}> $catalogoPeles
 * @var array<int, array{slug: string, emoji: string, nome: string, nivel: ?int, custoMoedas: ?int, estilo: string, cor: string}> $catalogoRoupas
 * @var array<int, array{slug: string, emoji: string, nome: string, nivel: ?int, custoMoedas: ?int}> $catalogoMascotes
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
$peleEquipada = KidsAvatar::encontrar($catalogoPeles, $crianca->avatarPele) ?? $catalogoPeles[0];
$roupaEquipada = KidsAvatar::encontrar($catalogoRoupas, $crianca->avatarRoupa) ?? $catalogoRoupas[0];
$estiloAtivo = static fn (string $estilo): string => $estilo === $roupaEquipada['estilo'] ? ' ativo' : '';

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
 * controller). Pele fica de fora: todos os tons ja vem desbloqueados,
 * nunca entram na loja.
 *
 * @return array<int, array{categoria: string, rotulo: string, item: array<string, mixed>}>
 */
$itensDaLojaNaoComprados = static function () use ($catalogoChapeus, $catalogoAcessorios, $catalogoFundos, $catalogoTitulos, $catalogoRoupas, $comprados): array {
    $porCategoria = [
        'roupa' => ['rotulo' => '👕 Roupa', 'catalogo' => $catalogoRoupas],
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
        <div class="kids-avatar-figura" style="--boneco-pele: <?= htmlspecialchars($peleEquipada['cor'], ENT_QUOTES, 'UTF-8') ?>; --boneco-roupa-cor: <?= htmlspecialchars($roupaEquipada['cor'], ENT_QUOTES, 'UTF-8') ?>;">
            <?php if ($chapeuEquipado !== null): ?>
                <span class="kids-avatar-chapeu"><?= $chapeuEquipado['emoji'] ?></span>
            <?php endif; ?>

            <?php if ($crianca->fotoPath !== null): ?>
                <span class="kids-avatar-boneco kids-avatar-boneco-foto">
                    <img src="<?= $basePath ?>/<?= htmlspecialchars($crianca->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                </span>
            <?php else: ?>
                <svg class="kids-boneco-svg" data-boneco-svg viewBox="0 0 200 240" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <defs>
                        <linearGradient id="kids-boneco-grad-arcoiris" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#FF6B6B"/>
                            <stop offset="25%" stop-color="#FFD93D"/>
                            <stop offset="50%" stop-color="#6BCB77"/>
                            <stop offset="75%" stop-color="#4CC9F0"/>
                            <stop offset="100%" stop-color="#9B5DE5"/>
                        </linearGradient>
                    </defs>

                    <rect x="72" y="163" width="22" height="55" rx="11" class="kids-boneco-pele"/>
                    <rect x="106" y="163" width="22" height="55" rx="11" class="kids-boneco-pele"/>
                    <rect x="38" y="96" width="20" height="62" rx="10" class="kids-boneco-pele"/>
                    <rect x="142" y="96" width="20" height="62" rx="10" class="kids-boneco-pele"/>
                    <rect x="60" y="83" width="80" height="85" rx="24" class="kids-boneco-pele"/>

                    <g class="kids-boneco-roupa-grupo<?= $estiloAtivo('camiseta_shorts') ?>" data-estilo="camiseta_shorts">
                        <rect x="55" y="86" width="90" height="55" rx="20" class="kids-boneco-roupa"/>
                        <rect x="68" y="158" width="30" height="30" rx="10" class="kids-boneco-roupa"/>
                        <rect x="102" y="158" width="30" height="30" rx="10" class="kids-boneco-roupa"/>
                    </g>
                    <g class="kids-boneco-roupa-grupo<?= $estiloAtivo('vestido') ?>" data-estilo="vestido">
                        <path d="M62,88 Q100,78 138,88 L150,213 Q100,228 50,213 Z" class="kids-boneco-roupa"/>
                    </g>
                    <g class="kids-boneco-roupa-grupo<?= $estiloAtivo('moletom_capuz') ?>" data-estilo="moletom_capuz">
                        <ellipse cx="100" cy="42" rx="53" ry="49" class="kids-boneco-roupa"/>
                        <rect x="52" y="86" width="96" height="90" rx="22" class="kids-boneco-roupa"/>
                    </g>
                    <g class="kids-boneco-roupa-grupo<?= $estiloAtivo('uniforme_capa') ?>" data-estilo="uniforme_capa">
                        <path d="M50,88 L45,188 L70,178 L75,93 Z" class="kids-boneco-roupa kids-boneco-capa"/>
                        <rect x="55" y="86" width="90" height="55" rx="20" class="kids-boneco-roupa"/>
                        <rect x="68" y="158" width="30" height="30" rx="10" class="kids-boneco-roupa"/>
                        <rect x="102" y="158" width="30" height="30" rx="10" class="kids-boneco-roupa"/>
                        <circle cx="100" cy="110" r="10" fill="#FFFFFF" opacity="0.85"/>
                    </g>
                    <g class="kids-boneco-roupa-grupo<?= $estiloAtivo('manto_longo') ?>" data-estilo="manto_longo">
                        <path d="M58,86 Q100,73 142,86 L150,213 Q100,230 50,213 Z" class="kids-boneco-roupa"/>
                        <rect x="55" y="138" width="90" height="12" fill="rgba(0,0,0,0.15)"/>
                    </g>

                    <circle cx="100" cy="50" r="40" class="kids-boneco-pele"/>
                    <path d="M62,36 Q100,8 138,36 L138,46 Q100,24 62,46 Z" fill="#4A3222"/>
                    <circle cx="85" cy="48" r="4" fill="#3A2E5C"/>
                    <circle cx="115" cy="48" r="4" fill="#3A2E5C"/>
                    <path d="M83,62 Q100,74 117,62" stroke="#3A2E5C" stroke-width="3.5" fill="none" stroke-linecap="round"/>
                </svg>
            <?php endif; ?>

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

        <h2 class="kids-secao-titulo">🎨 Tom de pele</h2>
        <div class="kids-item-grade kids-pele-grade">
            <?php foreach ($catalogoPeles as $item): ?>
                <label class="kids-pele-swatch<?= $peleEquipada['slug'] === $item['slug'] ? ' selecionado' : '' ?>">
                    <input type="radio" name="avatar_pele" value="<?= htmlspecialchars($item['slug'], ENT_QUOTES, 'UTF-8') ?>" <?= $peleEquipada['slug'] === $item['slug'] ? 'checked' : '' ?>>
                    <span class="kids-pele-circulo" style="background-color: <?= htmlspecialchars($item['cor'], ENT_QUOTES, 'UTF-8') ?>;"></span>
                    <span class="kids-item-nome"><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <h2 class="kids-secao-titulo">👕 Roupa</h2>
        <?php $renderGrade($catalogoRoupas, 'avatar_roupa', $roupaEquipada['slug'], $nivel, $comprados['roupa'], null); ?>

        <h2 class="kids-secao-titulo">🏅 Título</h2>
        <?php $renderGrade($catalogoTitulos, 'avatar_titulo', $crianca->avatarTitulo, $nivel, $comprados['titulo'], 'Sem título'); ?>

        <h2 class="kids-secao-titulo">🎩 Chapéu</h2>
        <?php $renderGrade($catalogoChapeus, 'avatar_chapeu', $crianca->avatarChapeu, $nivel, $comprados['chapeu'], 'Sem chapéu'); ?>

        <h2 class="kids-secao-titulo">🎒 Acessório</h2>
        <?php $renderGrade($catalogoAcessorios, 'avatar_acessorio', $crianca->avatarAcessorio, $nivel, $comprados['acessorio'], 'Sem acessório'); ?>

        <h2 class="kids-secao-titulo">🌈 Fundo</h2>
        <?php $renderGrade($catalogoFundos, 'avatar_fundo', $fundoEquipado['slug'], $nivel, $comprados['fundo'], null); ?>

        <h2 class="kids-secao-titulo">🦁 Mascote</h2>
        <p class="kids-login-subtitulo" style="text-align: left; margin-top: -0.6rem;">Seu bichinho de estimação, que te acompanha na Biblioteca inteira!</p>
        <?php $renderGrade($catalogoMascotes, 'avatar_mascote', $crianca->avatarMascote, $nivel, [], null); ?>

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
        // Preve visualiza o boné/acessório/fundo/título/pele/roupa assim
        // que a criança clica numa opção, sem esperar salvar+recarregar -
        // o formulário continua so gravando no banco quando "Salvar
        // visual" e enviado.
        var catalogos = {
            avatar_chapeu: <?= json_encode($catalogoChapeus, JSON_UNESCAPED_UNICODE) ?>,
            avatar_acessorio: <?= json_encode($catalogoAcessorios, JSON_UNESCAPED_UNICODE) ?>,
            avatar_fundo: <?= json_encode($catalogoFundos, JSON_UNESCAPED_UNICODE) ?>,
            avatar_titulo: <?= json_encode($catalogoTitulos, JSON_UNESCAPED_UNICODE) ?>,
            avatar_pele: <?= json_encode($catalogoPeles, JSON_UNESCAPED_UNICODE) ?>,
            avatar_roupa: <?= json_encode($catalogoRoupas, JSON_UNESCAPED_UNICODE) ?>
        };

        var estagio = document.querySelector('.kids-avatar-stage');
        var figura = document.querySelector('.kids-avatar-figura');
        var svg = figura.querySelector('[data-boneco-svg]');

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

        document.querySelectorAll('input[name="avatar_pele"]').forEach(function (input) {
            input.addEventListener('change', function () {
                var escolhido = itemDoCatalogo('avatar_pele', input.value);
                if (escolhido) {
                    figura.style.setProperty('--boneco-pele', escolhido.cor);
                }
            });
        });

        document.querySelectorAll('input[name="avatar_roupa"]').forEach(function (input) {
            input.addEventListener('change', function () {
                var escolhido = itemDoCatalogo('avatar_roupa', input.value);
                if (!escolhido || !svg) {
                    return;
                }
                figura.style.setProperty('--boneco-roupa-cor', escolhido.cor);
                svg.querySelectorAll('.kids-boneco-roupa-grupo').forEach(function (grupo) {
                    grupo.classList.toggle('ativo', grupo.dataset.estilo === escolhido.estilo);
                });
            });
        });
    })();
</script>
