<?php

/**
 * @var array $config
 * @var array<int, \Igrejas\Models\Louvor> $louvores
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
        <h1 class="dash-page-title">Louvores</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> louvor<?= $total === 1 ? '' : 'es' ?> cadastrado<?= $total === 1 ? '' : 's' ?> - letra, cifra, tom e histórico de mudanças.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/louvores/novo" class="btn-k btn-k-grad">
            <i class="bi bi-music-note-list"></i> Novo louvor
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/louvores" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por título ou letra..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/louvores" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($louvores === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-music-note-list"></i></div>
            <h2><?= $search !== '' ? 'Nenhum louvor encontrado' : 'Nenhum louvor cadastrado ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro termo.'
                    : 'Comece cadastrando o primeiro louvor: letra, cifra e tom pro time todo consultar.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/louvores/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-music-note-list"></i> Cadastrar louvor
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Louvor</th>
                        <th>Tom atual</th>
                        <th>Áudio vinculado</th>
                        <th>Status</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($louvores as $louvor): ?>
                        <tr>
                            <td>
                                <div class="crud-person">
                                    <span class="crud-avatar">
                                        <i class="bi bi-music-note-beamed" style="font-size: 0.95rem;"></i>
                                    </span>
                                    <a href="<?= $basePath ?>/dashboard/louvores/<?= $louvor->id ?>"><?= htmlspecialchars($louvor->titulo, ENT_QUOTES, 'UTF-8') ?></a>
                                </div>
                            </td>
                            <td>
                                <?= $louvor->tomAtual !== null
                                    ? htmlspecialchars($louvor->tomAtual, ENT_QUOTES, 'UTF-8')
                                    : '<span class="crud-text-dim">&mdash;</span>' ?>
                            </td>
                            <td>
                                <?= $louvor->playbackTitulo !== null
                                    ? htmlspecialchars($louvor->playbackTitulo, ENT_QUOTES, 'UTF-8')
                                    : '<span class="crud-text-dim">&mdash;</span>' ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $louvor->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $louvor->status === 'ativo' ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a
                                    href="<?= $basePath ?>/dashboard/louvores/<?= $louvor->id ?>"
                                    class="crud-icon-btn"
                                    aria-label="Visualizar <?= htmlspecialchars($louvor->titulo, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a
                                    href="<?= $basePath ?>/dashboard/louvores/<?= $louvor->id ?>/editar"
                                    class="crud-icon-btn"
                                    aria-label="Editar <?= htmlspecialchars($louvor->titulo, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/dashboard/louvores/<?= $louvor->id ?>/excluir"
                                    data-confirm="Remover o louvor &quot;<?= htmlspecialchars($louvor->titulo, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button
                                        type="submit"
                                        class="crud-icon-btn danger"
                                        aria-label="Excluir <?= htmlspecialchars($louvor->titulo, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($lastPage > 1): ?>
            <div class="crud-pagination">
                <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                    <a
                        href="<?= $basePath ?>/dashboard/louvores?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
