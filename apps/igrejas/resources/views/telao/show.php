<?php
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
</div>

<script type="application/json" id="telao-estado-inicial"><?= json_encode($estadoInicial) ?: 'null' ?></script>
<script src="<?= $basePath ?>/assets/js/telao.js"></script>
<script src="https://www.youtube.com/iframe_api"></script>
