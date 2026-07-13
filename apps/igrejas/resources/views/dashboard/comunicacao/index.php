<?php
/**
 * @var array $config
 * @var array<int, \Igrejas\Models\ComunicacaoAviso> $avisos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';

$statusLabels = ['rascunho' => 'Rascunho', 'publicado' => 'Publicado', 'arquivado' => 'Arquivado'];
$publicoLabels = ['todos' => 'Todos', 'lideranca' => 'Liderança'];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Comunicação</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> aviso<?= $total === 1 ? '' : 's' ?> cadastrado<?= $total === 1 ? '' : 's' ?>.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/comunicacao/novo" class="btn-k btn-k-grad">
            <i class="bi bi-megaphone"></i> Novo aviso
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/comunicacao" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por título ou conteúdo..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/comunicacao" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($avisos === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-megaphone"></i></div>
            <h2><?= $search !== '' ? 'Nenhum aviso encontrado' : 'Nenhum aviso cadastrado ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro termo.'
                    : 'Comece cadastrando o primeiro aviso ou comunicado para a igreja.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/comunicacao/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-megaphone"></i> Cadastrar aviso
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Aviso</th>
                        <th>Público</th>
                        <th>Publicação</th>
                        <th>Status</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avisos as $aviso): ?>
                        <tr>
                            <td>
                                <div class="crud-person">
                                    <span class="crud-avatar">
                                        <i class="bi bi-megaphone" style="font-size: 0.95rem;"></i>
                                    </span>
                                    <span><?= htmlspecialchars($aviso->titulo, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td><?= $publicoLabels[$aviso->publicoAlvo] ?? $aviso->publicoAlvo ?></td>
                            <td>
                                <?= $aviso->dataPublicacao
                                    ? (new DateTimeImmutable($aviso->dataPublicacao))->format('d/m/Y')
                                    : '<span class="crud-text-dim">&mdash;</span>' ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $aviso->status === 'publicado' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $statusLabels[$aviso->status] ?? $aviso->status ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a
                                    href="<?= $basePath ?>/dashboard/comunicacao/<?= $aviso->id ?>/editar"
                                    class="crud-icon-btn"
                                    aria-label="Editar <?= htmlspecialchars($aviso->titulo, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/dashboard/comunicacao/<?= $aviso->id ?>/excluir"
                                    data-confirm="Remover o aviso &quot;<?= htmlspecialchars($aviso->titulo, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button
                                        type="submit"
                                        class="crud-icon-btn danger"
                                        aria-label="Excluir <?= htmlspecialchars($aviso->titulo, ENT_QUOTES, 'UTF-8') ?>"
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
                        href="<?= $basePath ?>/dashboard/comunicacao?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
