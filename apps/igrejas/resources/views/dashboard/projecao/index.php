<?php
/**
 * @var array $config
 * @var \Igrejas\Models\ProjecaoSessao|null $sessao
 * @var \Igrejas\Models\ProjecaoEstado|null $estado
 * @var array<int, \Igrejas\Models\BibliaLivro> $livros
 * @var array<string, string> $versoes
 * @var bool $bibliaImportada
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$telaoUrl = $sessao ? $basePath . '/telao/' . $sessao->token : '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Projecao</h1>
        <p class="dash-page-subtitle">Controle o telao do culto: biblia, videos e o acesso do preletor.</p>
    </div>
</div>

<?php if (!$bibliaImportada): ?>
    <div class="crud-alert error">
        <i class="bi bi-exclamation-triangle"></i>
        <div>O texto da Biblia ainda nao foi importado. As referencias podem ser selecionadas, mas o texto so aparece apos a importacao.</div>
    </div>
<?php endif; ?>

<?php if (!$sessao): ?>
    <div class="crud-empty dash-panel">
        <div class="icon"><i class="bi bi-easel2"></i></div>
        <h2>Nenhuma sessao de projecao ativa</h2>
        <p>Inicie uma sessao para gerar o link do telao e o PIN de acesso do preletor.</p>
        <form method="POST" action="<?= $basePath ?>/dashboard/projecao/iniciar">
            <?= $csrf ?>
            <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-play-fill"></i> Iniciar sessao de projecao</button>
        </form>
    </div>
<?php else: ?>
    <div class="dash-panel projecao-sessao-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-broadcast"></i> Sessao ativa</h2>
            <span class="panel-badge" style="background: rgba(52,211,153,0.12); border-color: rgba(52,211,153,0.35); color: var(--success);">ao vivo</span>
        </div>

        <div class="projecao-acessos">
            <div class="projecao-acesso-card">
                <span class="label"><i class="bi bi-display"></i> Telao (projetor)</span>
                <p>Abra este link no computador ligado ao projetor.</p>
                <a href="<?= htmlspecialchars($telaoUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn-k btn-k-ghost">
                    <i class="bi bi-box-arrow-up-right"></i> Abrir telao
                </a>
            </div>
            <div class="projecao-acesso-card">
                <span class="label"><i class="bi bi-tablet"></i> Preletor (tablet do pastor)</span>
                <p>Peca para o pastor acessar <strong><?= $basePath ?>/preletor</strong> e digitar o PIN:</p>
                <div class="projecao-pin"><?= htmlspecialchars($sessao->pin, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <form method="POST" action="<?= $basePath ?>/dashboard/projecao/encerrar" data-confirm="Encerrar a sessao de projecao? O telao e o preletor perderao o acesso." style="margin-top: 1.2rem;">
            <?= $csrf ?>
            <button type="submit" class="btn-k btn-k-outline" style="border-color: rgba(248,113,113,0.4); color: var(--danger);">
                <i class="bi bi-stop-fill"></i> Encerrar sessao
            </button>
        </form>
    </div>

    <div class="dash-panels-row" data-projecao-controles data-token="<?= htmlspecialchars($sessao->token, ENT_QUOTES, 'UTF-8') ?>" data-poll-url="<?= $basePath ?>/projecao/<?= $sessao->token ?>/estado">
        <div class="dash-panel">
            <div class="dash-panel-head">
                <h2><i class="bi bi-book"></i> Biblia</h2>
            </div>
            <form class="crud-form" data-form-biblia onsubmit="return false;">
                <div class="crud-form-grid">
                    <div class="crud-field crud-field-full">
                        <label for="biblia_versao">Versao</label>
                        <select id="biblia_versao" data-campo="biblia_versao" required>
                            <?php foreach ($versoes as $codigo => $nome): ?>
                                <option value="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="crud-field crud-field-full">
                        <label for="livro_id">Livro</label>
                        <select id="livro_id" data-campo="livro_id" required>
                            <option value="" selected disabled>Selecione...</option>
                            <?php foreach ($livros as $livro): ?>
                                <option value="<?= $livro->id ?>" data-total-capitulos="<?= $livro->totalCapitulos ?>">
                                    <?= htmlspecialchars($livro->nome, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="crud-field">
                        <label for="capitulo">Capitulo</label>
                        <input type="number" id="capitulo" data-campo="capitulo" min="1" required>
                    </div>
                    <div class="crud-field">
                        <label for="versiculo_inicio">Versiculo</label>
                        <input type="number" id="versiculo_inicio" data-campo="versiculo_inicio" min="1" required>
                    </div>
                    <div class="crud-field crud-field-full">
                        <label for="versiculo_fim">Ate o versiculo (opcional)</label>
                        <input type="number" id="versiculo_fim" data-campo="versiculo_fim" min="1">
                    </div>
                </div>
                <div class="crud-form-actions" style="justify-content: flex-start;">
                    <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-broadcast"></i> Projetar no telao</button>
                </div>
            </form>

            <div class="projecao-nav" data-projecao-nav hidden>
                <div class="projecao-nav-atual">
                    <span class="label">Exibindo agora</span>
                    <strong data-nav-atual-ref>&mdash;</strong>
                </div>
                <div class="projecao-nav-botoes">
                    <button type="button" class="btn-k btn-k-ghost" data-nav-acao="anterior">
                        <i class="bi bi-arrow-left"></i> Anterior
                    </button>
                    <button type="button" class="btn-k btn-k-ghost" data-nav-acao="proximo">
                        Proximo <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
                <div class="projecao-nav-preview" data-nav-preview hidden>
                    <span class="label"><i class="bi bi-skip-forward"></i> A seguir</span>
                    <strong data-nav-preview-ref></strong>
                    <p data-nav-preview-texto></p>
                </div>
                <p class="projecao-nav-dica">Atalhos: <kbd>&larr;</kbd> anterior &middot; <kbd>&rarr;</kbd> proximo (fora dos campos do formulario)</p>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-head">
                <h2><i class="bi bi-youtube"></i> Video (YouTube)</h2>
            </div>
            <form class="crud-form" data-form-video onsubmit="return false;">
                <div class="crud-field">
                    <label for="video_url">Link do video</label>
                    <input type="url" id="video_url" placeholder="https://www.youtube.com/watch?v=..." required>
                </div>
                <div class="crud-form-actions" style="justify-content: flex-start;">
                    <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-play-circle"></i> Carregar video</button>
                </div>
            </form>

            <div class="projecao-video-controles">
                <button type="button" class="btn-k btn-k-ghost" data-video-acao="tocando"><i class="bi bi-play-fill"></i> Play</button>
                <button type="button" class="btn-k btn-k-ghost" data-video-acao="pausado"><i class="bi bi-pause-fill"></i> Pausar</button>
                <button type="button" class="btn-k btn-k-ghost" data-video-acao="fadeout"><i class="bi bi-moon-fill"></i> Fadeout</button>
            </div>
        </div>
    </div>

    <div class="dash-panel" style="margin-top: 1.1rem;">
        <div class="dash-panel-head">
            <h2><i class="bi bi-sliders"></i> Outras acoes</h2>
        </div>
        <div class="projecao-video-controles">
            <button type="button" class="btn-k btn-k-ghost" data-acao-logo><i class="bi bi-image"></i> Mostrar logo</button>
            <button type="button" class="btn-k btn-k-ghost" data-acao-limpar><i class="bi bi-x-circle"></i> Limpar tela</button>
        </div>
    </div>

    <script src="<?= $basePath ?>/assets/js/projecao-admin.js"></script>
<?php endif; ?>
