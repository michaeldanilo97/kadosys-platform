<?php

/**
 * @var array $config
 * @var array<int, \Igrejas\Models\Playback> $playbacks
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Playbacks</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> playback<?= $total === 1 ? '' : 's' ?> na biblioteca do ministerio de louvor.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/playbacks/novo" class="btn-k btn-k-grad">
            <i class="bi bi-upload"></i> Enviar playback
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/playbacks" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por titulo ou artista..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/playbacks" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($playbacks === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-music-note-beamed"></i></div>
            <h2><?= $search !== '' ? 'Nenhum playback encontrado' : 'Nenhum playback enviado ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro titulo ou artista.'
                    : 'Envie o primeiro audio da biblioteca do ministerio de louvor.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/playbacks/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-upload"></i> Enviar playback
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <ul class="playback-list">
            <?php foreach ($playbacks as $playback): ?>
                <li class="playback-item">
                    <div class="playback-item-head">
                        <div class="playback-item-info">
                            <span class="crud-avatar"><i class="bi bi-music-note-beamed"></i></span>
                            <div>
                                <div class="playback-item-titulo"><?= htmlspecialchars($playback->titulo, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($playback->artista): ?>
                                    <div class="crud-text-dim"><?= htmlspecialchars($playback->artista, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge <?= $playback->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                                <?= $playback->status === 'ativo' ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                        <div class="playback-item-actions">
                            <a
                                href="<?= $basePath ?>/dashboard/playbacks/<?= $playback->id ?>/editar"
                                class="crud-icon-btn"
                                aria-label="Editar <?= htmlspecialchars($playback->titulo, ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form
                                method="POST"
                                action="<?= $basePath ?>/dashboard/playbacks/<?= $playback->id ?>/excluir"
                                data-confirm="Remover o playback &quot;<?= htmlspecialchars($playback->titulo, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                            >
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <button
                                    type="submit"
                                    class="crud-icon-btn danger"
                                    aria-label="Excluir <?= htmlspecialchars($playback->titulo, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="playback-item-player">
                        <audio
                            controls
                            preload="none"
                            src="<?= $basePath ?>/<?= htmlspecialchars($playback->arquivoPath, ENT_QUOTES, 'UTF-8') ?>"
                        ></audio>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($lastPage > 1): ?>
            <div class="crud-pagination">
                <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                    <a
                        href="<?= $basePath ?>/dashboard/playbacks?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
