<?php

use Igrejas\Core\View;
use Igrejas\Models\Louvor;

/**
 * @var array $config
 * @var \Igrejas\Models\Repertorio $repertorio
 * @var bool $ehLider
 * @var array<int, \Igrejas\Models\RepertorioMensagem> $mensagensIniciais
 */
$basePath = $config['base_path'] ?? '';

$estadoInicial = $repertorio->paraJson();
$estadoInicial['mensagens'] = array_map(
    static fn ($mensagem) => $mensagem->paraJson(),
    $mensagensIniciais
);
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/repertorio-culto.css?v=<?= View::assetVersion('assets/css/repertorio-culto.css') ?>">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/culto-offline.css?v=<?= View::assetVersion('assets/css/culto-offline.css') ?>">

<div
    data-culto-root
    data-eh-lider="<?= $ehLider ? '1' : '0' ?>"
    data-estado-url="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/estado"
    data-avancar-url="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/avancar"
    data-voltar-url="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/voltar"
    data-mensagem-url="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/mensagens"
    data-tom-url-base="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/itens"
    data-sw-url="<?= $basePath ?>/service-worker.js"
    data-sw-scope="<?= $basePath ?>/dashboard/louvores/"
    data-sw-offline-msg="Aparelho sem internet - mostrando a última cifra/tom recebidos. Avançar/voltar música, mudar tom e o chat estão inativos até a conexão voltar."
>
    <script id="culto-estado-inicial" type="application/json"><?= json_encode($estadoInicial, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    <?php if ($ehLider): ?>
        <script id="culto-tons">window.KADOSYS_TONS = <?= json_encode(['maiores' => Louvor::TONS_MAIORES, 'menores' => Louvor::TONS_MENORES], JSON_HEX_TAG) ?>;</script>
    <?php endif; ?>

    <div class="mc-topo">
        <div>
            <span class="mc-repertorio-titulo"><?= htmlspecialchars($repertorio->titulo, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="mc-proximo" data-culto-proximo></span>
        </div>
        <button type="button" class="mc-btn mc-btn-chat" data-culto-chat-toggle>
            <i class="bi bi-chat-dots"></i>
            <span data-culto-chat-badge class="mc-chat-badge" hidden>0</span>
        </button>
    </div>

    <div class="mc-atual" data-culto-atual>
        <p class="mc-vazio" data-culto-vazio>
            <?= $ehLider ? 'Toque em "Próxima" para começar.' : 'Aguardando o líder iniciar o culto...' ?>
        </p>
        <div data-culto-conteudo hidden>
            <h1 class="mc-titulo" data-culto-titulo></h1>
            <div class="mc-meta">
                <span data-culto-tom></span>
                <?php if ($ehLider): ?>
                    <span class="mc-tom-controles" data-culto-tom-controles hidden>
                        <button type="button" class="mc-tom-btn" data-culto-tom-baixar aria-label="Baixar meio tom"><i class="bi bi-dash-lg"></i></button>
                        <select data-culto-tom-select aria-label="Escolher tom"></select>
                        <button type="button" class="mc-tom-btn" data-culto-tom-subir aria-label="Subir meio tom"><i class="bi bi-plus-lg"></i></button>
                    </span>
                <?php endif; ?>
                <span data-culto-bpm></span>
            </div>
            <div class="mc-abas">
                <button type="button" class="mc-aba is-ativa" data-culto-aba="letra">Letra</button>
                <button type="button" class="mc-aba" data-culto-aba="cifra">Cifra</button>
            </div>
            <div class="mc-texto" data-culto-texto="letra"></div>
            <div class="mc-texto" data-culto-texto="cifra" hidden></div>
        </div>
    </div>

    <?php if ($ehLider): ?>
        <div class="mc-controles-lider">
            <button type="button" class="mc-btn" data-culto-voltar><i class="bi bi-skip-backward-fill"></i> Anterior</button>
            <button type="button" class="mc-btn mc-btn-grad" data-culto-avancar><i class="bi bi-skip-forward-fill"></i> Próxima</button>
        </div>
    <?php endif; ?>

    <div class="mc-chat" data-culto-chat hidden>
        <div class="mc-chat-head">
            <span>Avisos rápidos</span>
            <button type="button" class="mc-chat-fechar" data-culto-chat-fechar aria-label="Fechar"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="mc-chat-lista" data-culto-chat-lista></div>
        <form class="mc-chat-form" data-culto-chat-form>
            <input type="text" maxlength="280" placeholder="Ex.: abaixa meio tom" data-culto-chat-input>
            <button type="submit" aria-label="Enviar"><i class="bi bi-send-fill"></i></button>
        </form>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/repertorio-culto.js?v=<?= View::assetVersion('assets/js/repertorio-culto.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/culto-offline.js?v=<?= View::assetVersion('assets/js/culto-offline.js') ?>"></script>
