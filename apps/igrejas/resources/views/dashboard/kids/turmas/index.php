<?php
/**
 * @var array $config
 * @var array<int, \Igrejas\Models\KidsTurma> $turmas
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
        <h1 class="dash-page-title">Kids &middot; Turmas</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> turma<?= $total === 1 ? '' : 's' ?> cadastrada<?= $total === 1 ? '' : 's' ?>.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/kids" class="btn-k btn-k-ghost"><i class="bi bi-arrow-left"></i> Kids</a>
        <a href="<?= $basePath ?>/dashboard/kids/turmas/novo" class="btn-k btn-k-grad">
            <i class="bi bi-plus-lg"></i> Nova turma
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/kids/turmas" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por nome da turma..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/kids/turmas" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($turmas === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-collection"></i></div>
            <h2><?= $search !== '' ? 'Nenhuma turma encontrada' : 'Nenhuma turma cadastrada ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro nome.'
                    : 'Comece cadastrando a primeira turma do ministério infantil.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/kids/turmas/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-plus-lg"></i> Cadastrar turma
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Turma</th>
                        <th>Faixa etária</th>
                        <th>Professor</th>
                        <th>Crianças</th>
                        <th>Status</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($turmas as $turma): ?>
                        <tr>
                            <td>
                                <div class="crud-person">
                                    <span class="crud-avatar">
                                        <?= htmlspecialchars(mb_strtoupper(mb_substr($turma->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span><?= htmlspecialchars($turma->nome, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if ($turma->faixaEtariaMin !== null || $turma->faixaEtariaMax !== null): ?>
                                    <?= $turma->faixaEtariaMin ?? '0' ?>&ndash;<?= $turma->faixaEtariaMax ?? '+' ?> anos
                                <?php else: ?>
                                    <span class="crud-text-dim">Não definida</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $turma->professorNome
                                    ? htmlspecialchars($turma->professorNome, ENT_QUOTES, 'UTF-8')
                                    : '<span class="crud-text-dim">Sem professor definido</span>' ?>
                            </td>
                            <td><?= $turma->totalCriancas ?></td>
                            <td>
                                <span class="status-badge <?= $turma->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $turma->status === 'ativo' ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a
                                    href="<?= $basePath ?>/dashboard/kids/turmas/<?= $turma->id ?>/editar"
                                    class="crud-icon-btn"
                                    aria-label="Editar <?= htmlspecialchars($turma->nome, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/dashboard/kids/turmas/<?= $turma->id ?>/excluir"
                                    data-confirm="Remover a turma &quot;<?= htmlspecialchars($turma->nome, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button
                                        type="submit"
                                        class="crud-icon-btn danger"
                                        aria-label="Excluir <?= htmlspecialchars($turma->nome, ENT_QUOTES, 'UTF-8') ?>"
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
                        href="<?= $basePath ?>/dashboard/kids/turmas?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
