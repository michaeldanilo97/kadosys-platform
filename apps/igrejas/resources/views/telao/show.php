<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var string $token
 * @var string|null $logoPath
 * @var array|null $estadoInicial
 */
$basePath = $config['base_path'] ?? '';
$logoUrl = $logoPath ? $basePath . '/' . $logoPath : null;
?>

<div
    class="telao"
    data-telao
    data-poll-url="<?= $basePath ?>/projecao/<?= urlencode($token) ?>/estado"
    data-logo-url="<?= htmlspecialchars($logoUrl ?? '', ENT_QUOTES, 'UTF-8') ?>"
>
    <div class="telao-layer telao-blank" data-telao-layer="blank"></div>

    <div class="telao-layer telao-video" data-telao-layer="video">
        <div id="telao-player"></div>
        <div class="telao-video-overlay" data-telao-video-overlay></div>
    </div>

    <div class="telao-layer telao-biblia" data-telao-layer="biblia">
        <div class="telao-stage" data-telao-stage>
            <div class="stage-biblia">
                <div class="stage-biblia-texto" data-telao-biblia-texto></div>
                <div class="stage-biblia-ref" data-telao-biblia-ref></div>
            </div>
            <canvas class="stage-marcacao" data-telao-marcacao></canvas>
        </div>
    </div>

    <div class="telao-layer telao-logo" data-telao-layer="logo">
        <?php if ($logoUrl): ?>
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" data-telao-logo-img>
        <?php else: ?>
            <span class="telao-logo-fallback" data-telao-logo-fallback>KADOSYS Igrejas</span>
        <?php endif; ?>
    </div>

    <div class="telao-layer telao-pix" data-telao-layer="pix">
        <div class="telao-pix-aviso" data-telao-pix-aviso hidden></div>
        <div class="telao-pix-grupo" data-telao-pix-grupo>
            <div class="telao-pix-card telao-pix-card-esquerda">
                <div class="telao-pix-titulo">Dízimo</div>
                <div class="telao-pix-qr" data-telao-pix-qr="dizimo"></div>
            </div>
            <div class="telao-pix-centro">
                <?php if ($logoUrl): ?>
                    <img class="telao-pix-logo" src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo">
                <?php endif; ?>
                <div class="telao-pix-mensagem" data-telao-pix-mensagem hidden></div>
                <div class="telao-pix-instrucao" data-telao-pix-instrucao></div>
            </div>
            <div class="telao-pix-card telao-pix-card-direita">
                <div class="telao-pix-titulo">Oferta</div>
                <div class="telao-pix-qr" data-telao-pix-qr="oferta"></div>
            </div>
        </div>
    </div>

    <div class="telao-layer telao-imagem" data-telao-layer="imagem">
        <img data-telao-imagem-img alt="">
    </div>

    <div class="telao-aviso-audio" data-telao-audio-unlock>
        <i class="bi bi-volume-up-fill"></i> Toque na tela uma vez para habilitar o audio do video e a leitura em voz alta
    </div>
</div>

<script type="application/json" id="telao-estado-inicial"><?= json_encode($estadoInicial) ?: 'null' ?></script>
<script src="<?= $basePath ?>/assets/js/vendor/qrcode-generator.js?v=<?= View::assetVersion('assets/js/vendor/qrcode-generator.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/telao.js?v=<?= View::assetVersion('assets/js/telao.js') ?>"></script>
<script src="https://www.youtube.com/iframe_api"></script>
