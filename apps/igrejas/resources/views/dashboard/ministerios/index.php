<?php
/**
 * @var array $config
 * @var array<int, \Igrejas\Models\Ministerio> $ministerios
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
        <h1 class="dash-page-title">Ministerios</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> ministerio<?= $total === 1 ? '' : 's' ?> cadastrado<?= $total === 1 ? '' : 's' ?>.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/ministerios/novo" class="btn-k btn-k-grad">
            <i class="bi bi-diagram-3"></i> Novo ministerio
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/ministerios" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por nome do ministerio..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/ministerios" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($ministerios === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-diagram-3"></i></div>
            <h2><?= $search !== '' ? 'Nenhum ministerio encontrado' : 'Nenhum ministerio cadastrado ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro nome.'
                    : 'Comece cadastrando o primeiro ministerio da igreja.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/ministerios/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-diagram-3"></i> Cadastrar ministerio
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Ministerio</th>
                        <th>Lider</th>
                        <th>Voluntarios</th>
                        <th>Status</th>
                        <th class="actions-col">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ministerios as $ministerio): ?>
                        <tr>
                            <td>
                                <div class="crud-person">
                                    <span class="crud-avatar">
                                        <?= htmlspecialchars(mb_strtoupper(mb_substr($ministerio->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span><?= htmlspecialchars($ministerio->nome, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td>
                                <?= $ministerio->liderNome
                                    ? htmlspecialchars($ministerio->liderNome, ENT_QUOTES, 'UTF-8')
                                    : '<span class="crud-text-dim">Sem lider definido</span>' ?>
                            </td>
                            <td><?= $ministerio->totalVoluntarios ?></td>
                            <td>
                                <span class="status-badge <?= $ministerio->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $ministerio->status === 'ativo' ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a
                                    href="<?= $basePath ?>/dashboard/ministerios/<?= $ministerio->id ?>/editar"
                                    class="crud-icon-btn"
                                    aria-label="Editar <?= htmlspecialchars($ministerio->nome, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/dashboard/ministerios/<?= $ministerio->id ?>/excluir"
                                    data-confirm="Remover o ministerio &quot;<?= htmlspecialchars($ministerio->nome, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button
                                        type="submit"
                                        class="crud-icon-btn danger"
                                        aria-label="Excluir <?= htmlspecialchars($ministerio->nome, ENT_QUOTES, 'UTF-8') ?>"
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
                        href="<?= $basePath ?>/dashboard/ministerios?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
