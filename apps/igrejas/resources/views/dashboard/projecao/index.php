<?php

use Igrejas\Core\View;

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
                <span class="label"><i class="bi bi-display"></i> Telao (projetor ou TV)</span>
                <p>No computador ligado ao projetor, abra o link abaixo:</p>
                <a href="<?= htmlspecialchars($telaoUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn-k btn-k-ghost">
                    <i class="bi bi-box-arrow-up-right"></i> Abrir telao
                </a>
                <p class="projecao-acesso-alt">Numa Smart TV, digitar esse link pelo controle remoto e dificil - acesse <strong><?= $basePath ?>/telao</strong> e digite o mesmo PIN do preletor ao lado.</p>
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

    <div data-projecao-controles data-token="<?= htmlspecialchars($sessao->token, ENT_QUOTES, 'UTF-8') ?>" data-poll-url="<?= $basePath ?>/projecao/<?= $sessao->token ?>/estado" data-capitulo-info-url="<?= $basePath ?>/projecao/<?= $sessao->token ?>/biblia/capitulo">
        <div class="dash-panel biblia-panel-full">
            <div class="dash-panel-head">
                <h2><i class="bi bi-book"></i> Biblia</h2>
                <span class="panel-badge"><i class="bi bi-lightning-charge-fill"></i> projeta ao clicar</span>
            </div>

            <div class="biblia-picker" data-form-biblia data-biblia-picker>
                <div class="biblia-picker-controles">
                    <div class="biblia-linha-topo">
                        <div>
                            <div class="biblia-secao-label">Versao</div>
                            <div class="biblia-versoes" data-versao-pills>
                                <input type="hidden" data-campo="biblia_versao" required>
                                <?php foreach ($versoes as $codigo => $nome): ?>
                                    <button type="button" class="biblia-pill" data-versao-pill data-valor="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= strtoupper(htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8')) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="biblia-livro-field">
                            <div class="biblia-secao-label">Livro</div>
                            <div class="livro-combo" data-livro-combo>
                                <i class="bi bi-search"></i>
                                <input type="text" class="livro-combo-input" data-livro-combo-input placeholder="Buscar livro (ex.: joao, salmos, gn)..." autocomplete="off" aria-expanded="false">
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
                            <p class="biblia-livro-dica" data-livro-dica>
                                <i class="bi bi-arrow-up-short"></i> Escolha um livro para ver os capitulos e versiculos
                            </p>
                        </div>
                    </div>

                    <div class="biblia-secao" data-secao-capitulo>
                        <div class="biblia-secao-label"><i class="bi bi-bookmark"></i> Capitulo</div>
                        <input type="hidden" data-campo="capitulo" required>
                        <div class="biblia-chips" data-capitulo-chips></div>
                    </div>

                    <div class="biblia-secao" data-secao-versiculo>
                        <div class="biblia-secao-label"><i class="bi bi-text-paragraph"></i> Versiculo <span class="hint">(shift+clique seleciona um intervalo)</span></div>
                        <input type="hidden" data-campo="versiculo_inicio" required>
                        <input type="hidden" data-campo="versiculo_fim">
                        <div class="biblia-chips" data-versiculo-chips></div>
                    </div>
                </div>

                <div class="biblia-picker-sidebar">
                    <div class="biblia-preview-live" data-preview-live>
                        <div class="ref" data-preview-ref><i class="bi bi-broadcast"></i> Nada em projecao</div>
                        <div class="texto" data-preview-texto><span class="vazio">Escolha versao, livro, capitulo e versiculo acima para comecar.</span></div>
                        <div class="biblia-preview-proximo" data-preview-proximo hidden>
                            <div class="label"><i class="bi bi-skip-forward"></i> A seguir</div>
                            <div data-preview-proximo-texto></div>
                        </div>
                    </div>

                    <div class="biblia-nav-row" data-projecao-nav hidden>
                        <button type="button" class="btn-k btn-k-ghost" data-nav-acao="anterior">
                            <i class="bi bi-arrow-left"></i> Anterior
                        </button>
                        <button type="button" class="btn-k btn-k-ghost" data-nav-acao="proximo">
                            Proximo <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                    <button type="button" class="btn-k btn-k-ghost" data-acao-ler-agora hidden style="width: 100%; margin-top: 0.6rem;">
                        <i class="bi bi-megaphone-fill"></i> Ler agora no telao
                    </button>
                    <p class="projecao-nav-dica">Atalhos: <kbd>&larr;</kbd> anterior &middot; <kbd>&rarr;</kbd> proximo (fora dos campos de busca)</p>
                </div>
            </div>
        </div>

        <div class="dash-panels-row" style="margin-top: 1.1rem;">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <h2><i class="bi bi-youtube"></i> Video (YouTube)</h2>
                </div>
                <div class="crud-alert" style="background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.3); color: var(--primary-soft);">
                    <i class="bi bi-info-circle"></i>
                    Recomendamos usar uma conta com YouTube Premium para reproduzir os videos aqui - sem ele, o video pode exibir anuncios durante a exibicao no telao.
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

                <div class="video-progresso" data-video-progresso hidden>
                    <div class="video-progresso-barra">
                        <div class="video-progresso-preenchido" data-video-progresso-preenchido></div>
                    </div>
                    <span class="video-progresso-tempo" data-video-progresso-tempo>00:00 / 00:00</span>
                </div>
            </div>

            <div class="dash-panel">
                <div class="dash-panel-head">
                    <h2><i class="bi bi-sliders"></i> Outras acoes</h2>
                </div>
                <div class="projecao-video-controles">
                    <button type="button" class="btn-k btn-k-ghost" data-acao-logo><i class="bi bi-image"></i> Mostrar logo</button>
                    <button type="button" class="btn-k btn-k-ghost" data-acao-limpar><i class="bi bi-x-circle"></i> Limpar tela</button>
                    <button type="button" class="btn-k btn-k-ghost" data-acao-fullscreen><i class="bi bi-arrows-fullscreen"></i> Tela cheia</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= $basePath ?>/assets/js/biblia-picker.js?v=<?= View::assetVersion('assets/js/biblia-picker.js') ?>"></script>
    <script src="<?= $basePath ?>/assets/js/projecao-admin.js?v=<?= View::assetVersion('assets/js/projecao-admin.js') ?>"></script>
<?php endif; ?>
