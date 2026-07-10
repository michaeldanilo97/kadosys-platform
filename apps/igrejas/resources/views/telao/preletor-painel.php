<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var string $token
 * @var array<int, \Igrejas\Models\BibliaLivro> $livros
 * @var array<string, string> $versoes
 */
$basePath = $config['base_path'] ?? '';
?>

<div
    class="preletor-shell"
    data-preletor
    data-poll-url="<?= $basePath ?>/projecao/<?= urlencode($token) ?>/estado"
    data-biblia-url="<?= $basePath ?>/projecao/<?= urlencode($token) ?>/biblia"
    data-navegar-url="<?= $basePath ?>/projecao/<?= urlencode($token) ?>/biblia/navegar"
    data-capitulo-info-url="<?= $basePath ?>/projecao/<?= urlencode($token) ?>/biblia/capitulo"
    data-marcacao-url="<?= $basePath ?>/projecao/<?= urlencode($token) ?>/biblia/marcacao"
>
    <header class="preletor-topbar">
        <span class="brand"><span class="dot"></span> Preletor &middot; ao vivo</span>
        <span class="preletor-comando-indicador" data-comando-indicador hidden><i class="bi bi-person-fill"></i> <span data-comando-indicador-texto></span></span>
        <form method="POST" action="<?= $basePath ?>/preletor/sair">
            <button type="submit" class="preletor-sair"><i class="bi bi-box-arrow-right"></i> Sair</button>
        </form>
    </header>

    <div class="preletor-body">
        <form class="preletor-picker" data-preletor-form onsubmit="return false;">
            <div class="preletor-field">
                <label>Versao</label>
                <div class="preletor-versoes" data-versao-pills>
                    <input type="hidden" data-campo="biblia_versao" required>
                    <?php foreach ($versoes as $codigo => $nome): ?>
                        <button type="button" class="biblia-pill" data-versao-pill data-valor="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>">
                            <?= strtoupper(htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8')) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="preletor-field preletor-field-livro">
                <label for="preletor_livro_busca">Livro</label>
                <div class="livro-combo" data-livro-combo>
                    <input type="text" id="preletor_livro_busca" class="livro-combo-input" data-livro-combo-input placeholder="Buscar livro..." autocomplete="off" aria-expanded="false">
                    <input type="hidden" data-campo="livro_id" required>
                    <div class="livro-combo-lista" data-livro-combo-lista hidden>
                        <?php $testamentoAtual = null; ?>
                        <?php foreach ($livros as $livro): ?>
                            <?php if ($livro->testamento !== $testamentoAtual): $testamentoAtual = $livro->testamento; ?>
                                <div class="livro-combo-grupo" data-livro-combo-grupo><?= $testamentoAtual === 'antigo' ? 'Antigo Testamento' : 'Novo Testamento' ?></div>
                            <?php endif; ?>
                            <button type="button" class="livro-combo-item" data-livro-combo-item data-livro-id="<?= $livro->id ?>" data-nome="<?= htmlspecialchars($livro->nome, ENT_QUOTES, 'UTF-8') ?>" data-total-capitulos="<?= $livro->totalCapitulos ?>">
                                <?= htmlspecialchars($livro->nome, ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="preletor-field preletor-field-sm">
                <label>Cap.</label>
                <div class="num-combo" data-num-combo="capitulo">
                    <button type="button" class="num-combo-toggle" data-num-combo-toggle aria-expanded="false">Cap...</button>
                    <input type="hidden" data-campo="capitulo" required>
                    <div class="livro-combo-lista num-combo-lista" data-num-combo-lista hidden>
                        <div class="biblia-chips" data-num-combo-grid></div>
                    </div>
                </div>
            </div>
            <div class="preletor-field preletor-field-sm">
                <label>Vers.</label>
                <div class="num-combo" data-num-combo="versiculo_inicio">
                    <button type="button" class="num-combo-toggle" data-num-combo-toggle aria-expanded="false">Vers...</button>
                    <input type="hidden" data-campo="versiculo_inicio" required>
                    <div class="livro-combo-lista num-combo-lista" data-num-combo-lista hidden>
                        <div class="biblia-chips" data-num-combo-grid></div>
                    </div>
                </div>
            </div>
            <div class="preletor-field preletor-field-sm">
                <label>Ate <i class="bi bi-info-circle" title="Intervalos longos (muitos versiculos de uma vez) diminuem automaticamente a letra no telao pra caber tudo - prefira intervalos curtos se a legibilidade for importante."></i></label>
                <div class="num-combo" data-num-combo="versiculo_fim">
                    <button type="button" class="num-combo-toggle" data-num-combo-toggle aria-expanded="false" title="Intervalos longos diminuem a letra no telao pra caber tudo.">Opcional</button>
                    <input type="hidden" data-campo="versiculo_fim">
                    <div class="livro-combo-lista num-combo-lista" data-num-combo-lista hidden>
                        <div class="biblia-chips" data-num-combo-grid></div>
                    </div>
                </div>
            </div>

            <div class="preletor-field-actions">
                <button type="submit" data-preletor-projetar><i class="bi bi-broadcast"></i> Projetar</button>
                <button type="button" class="preletor-nav-btn" data-nav-acao="anterior" aria-label="Versiculo anterior">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <button type="button" class="preletor-nav-btn" data-nav-acao="proximo" aria-label="Proximo versiculo">
                    <i class="bi bi-arrow-right"></i>
                </button>
                <span class="preletor-actions-divisor"></span>
                <button type="button" class="preletor-nav-btn" data-tool-pen aria-label="Ativar caneta" aria-pressed="false" title="Caneta">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button type="button" class="preletor-nav-btn" data-tool-clear aria-label="Apagar marcacao" title="Apagar marcacao">
                    <i class="bi bi-eraser-fill"></i>
                </button>
                <button type="button" class="preletor-nav-btn" data-tool-fullscreen aria-label="Tela cheia" aria-pressed="false" title="Tela cheia">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
                <button type="button" class="preletor-nav-btn" data-tool-ajuda aria-label="Ajuda sobre os botoes" title="O que cada botao faz">
                    <i class="bi bi-question-lg"></i>
                </button>
            </div>
        </form>

        <div class="preletor-ajuda-popup" data-preletor-ajuda-popup hidden>
            <button type="button" class="preletor-ajuda-fechar" data-preletor-ajuda-fechar aria-label="Fechar ajuda">
                <i class="bi bi-x-lg"></i>
            </button>
            <strong>O que cada botao faz</strong>
            <ul>
                <li><i class="bi bi-broadcast"></i> <strong>Projetar</strong> &middot; envia a referencia escolhida para o telao</li>
                <li><i class="bi bi-arrow-left"></i><i class="bi bi-arrow-right"></i> <strong>Setas</strong> &middot; versiculo anterior / proximo (ja projeta direto)</li>
                <li><i class="bi bi-pencil-fill"></i> <strong>Caneta</strong> &middot; ativa/desativa a marcacao a lapis sobre o texto (aparece tambem no telao)</li>
                <li><i class="bi bi-eraser-fill"></i> <strong>Apagar</strong> &middot; limpa a marcacao atual</li>
                <li><i class="bi bi-arrows-fullscreen"></i> <strong>Tela cheia</strong> &middot; modo foco, esconde menus para caber mais texto</li>
            </ul>
        </div>

        <div class="preletor-preview" data-nav-preview hidden>
            <span class="label"><i class="bi bi-skip-forward"></i> A seguir</span>
            <strong data-nav-preview-ref></strong>
            <span data-nav-preview-texto></span>
        </div>

        <div class="preletor-canvas-wrap" data-preletor-canvas-wrap>
            <div class="preletor-stage" data-preletor-stage>
                <div class="stage-biblia">
                    <div class="preletor-texto stage-biblia-texto" data-preletor-texto>
                        <p class="preletor-empty">Escolha a versao, livro, capitulo e versiculo acima para projetar.</p>
                    </div>
                    <div class="stage-biblia-ref" data-preletor-ref></div>
                </div>
                <canvas class="preletor-canvas stage-marcacao" data-preletor-canvas></canvas>
            </div>
        </div>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/preletor.js?v=<?= View::assetVersion('assets/js/preletor.js') ?>"></script>
