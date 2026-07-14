<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var \Igrejas\Models\Louvor $louvor
 */
$basePath = $config['base_path'] ?? '';
$temCifra = $louvor->cifra !== null && trim($louvor->cifra) !== '';
$temLetra = $louvor->letra !== null && trim($louvor->letra) !== '';
$abaInicial = $temCifra ? 'cifra' : 'letra';
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/louvor-tela-cheia.css?v=<?= View::assetVersion('assets/css/louvor-tela-cheia.css') ?>">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/culto-offline.css?v=<?= View::assetVersion('assets/css/culto-offline.css') ?>">

<div data-lct-root data-sw-scope="<?= $basePath ?>/dashboard/louvores/" data-sw-url="<?= $basePath ?>/service-worker.js" data-sw-offline-msg="Aparelho sem internet - mostrando a última versão salva desta cifra/letra.">
    <div class="lct-topo">
        <div>
            <h1 class="lct-titulo"><?= htmlspecialchars($louvor->titulo, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if ($louvor->tomAtual !== null): ?>
                <span class="lct-tom">Tom: <?= htmlspecialchars($louvor->tomAtual, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <?php if ($louvor->andamentoBpm !== null): ?>
                <span class="lct-tom"><?= $louvor->andamentoBpm ?> BPM</span>
            <?php endif; ?>
        </div>
        <div class="lct-abas">
            <button type="button" class="lct-aba <?= $abaInicial === 'letra' ? 'is-ativa' : '' ?>" data-lct-aba="letra">Letra</button>
            <button type="button" class="lct-aba <?= $abaInicial === 'cifra' ? 'is-ativa' : '' ?>" data-lct-aba="cifra">Cifra</button>
        </div>
    </div>

    <div class="lct-conteudo">
        <?php if ($temLetra): ?>
            <div class="lct-titulo-impressao">Letra<?= $temCifra ? ' (com cifra)' : '' ?></div>
            <div class="lct-texto is-letra" data-lct-texto="letra" <?= $abaInicial === 'letra' ? '' : 'hidden' ?>>
                <?= htmlspecialchars($louvor->letra, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php else: ?>
            <div class="lct-texto is-letra" data-lct-texto="letra" <?= $abaInicial === 'letra' ? '' : 'hidden' ?>>
                <span class="lct-vazio">Letra ainda não cadastrada.</span>
            </div>
        <?php endif; ?>

        <?php if ($temCifra): ?>
            <div class="lct-titulo-impressao">Cifra</div>
            <div class="lct-texto" data-lct-texto="cifra" <?= $abaInicial === 'cifra' ? '' : 'hidden' ?>>
                <?= htmlspecialchars($louvor->cifra, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php else: ?>
            <div class="lct-texto" data-lct-texto="cifra" <?= $abaInicial === 'cifra' ? '' : 'hidden' ?>>
                <span class="lct-vazio">Cifra ainda não cadastrada.</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="lct-controles">
        <a href="<?= $basePath ?>/dashboard/louvores/<?= $louvor->id ?>" class="lct-btn"><i class="bi bi-arrow-left"></i> Voltar</a>
        <div class="lct-controles-grupo">
            <button type="button" class="lct-btn" data-lct-fonte-menos aria-label="Diminuir fonte"><i class="bi bi-dash-lg"></i></button>
            <span class="lct-controles-label">Fonte</span>
            <button type="button" class="lct-btn" data-lct-fonte-mais aria-label="Aumentar fonte"><i class="bi bi-plus-lg"></i></button>
        </div>
        <div class="lct-controles-grupo">
            <button type="button" class="lct-btn" data-lct-autoscroll><i class="bi bi-play-fill"></i> Auto-scroll</button>
            <span class="lct-controles-label">Velocidade</span>
            <input type="range" min="1" max="10" value="2" class="lct-slider" data-lct-velocidade aria-label="Velocidade do auto-scroll">
        </div>
        <button type="button" class="lct-btn" data-lct-fullscreen><i class="bi bi-arrows-fullscreen"></i> Tela cheia</button>
        <button type="button" class="lct-btn" data-lct-pdf><i class="bi bi-file-earmark-pdf"></i> Baixar PDF</button>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/louvor-tela-cheia.js?v=<?= View::assetVersion('assets/js/louvor-tela-cheia.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/culto-offline.js?v=<?= View::assetVersion('assets/js/culto-offline.js') ?>"></script>
