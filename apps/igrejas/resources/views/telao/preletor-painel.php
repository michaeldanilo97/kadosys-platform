<?php
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
        <form method="POST" action="<?= $basePath ?>/preletor/sair">
            <button type="submit" class="preletor-sair"><i class="bi bi-box-arrow-right"></i> Sair</button>
        </form>
    </header>

    <div class="preletor-body">
        <form class="preletor-picker" data-preletor-form onsubmit="return false;">
            <div class="preletor-field">
                <label for="preletor_versao">Versao</label>
                <select id="preletor_versao" name="biblia_versao" data-campo="biblia_versao" required>
                    <?php foreach ($versoes as $codigo => $nome): ?>
                        <option value="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>">
                            <?= strtoupper(htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
                <label for="preletor_capitulo">Cap.</label>
                <select id="preletor_capitulo" data-campo="capitulo" required>
                    <option value="" selected disabled>Cap...</option>
                </select>
            </div>
            <div class="preletor-field preletor-field-sm">
                <label for="preletor_versiculo_inicio">Vers.</label>
                <select id="preletor_versiculo_inicio" data-campo="versiculo_inicio" required>
                    <option value="" selected disabled>Vers...</option>
                </select>
            </div>
            <div class="preletor-field preletor-field-sm">
                <label for="preletor_versiculo_fim">Ate</label>
                <select id="preletor_versiculo_fim" data-campo="versiculo_fim">
                    <option value="">Opcional</option>
                </select>
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
                <button type="button" class="preletor-nav-btn" data-tool-pen aria-label="Ativar caneta" title="Caneta">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button type="button" class="preletor-nav-btn" data-tool-clear aria-label="Apagar marcacao" title="Apagar marcacao">
                    <i class="bi bi-eraser-fill"></i>
                </button>
                <button type="button" class="preletor-nav-btn" data-tool-fullscreen aria-label="Tela cheia" title="Tela cheia">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
            </div>
        </form>

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

<script src="<?= $basePath ?>/assets/js/preletor.js"></script>
