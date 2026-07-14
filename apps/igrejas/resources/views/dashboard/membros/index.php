<?php
/**
 * @var array $config
 * @var array<int, \Igrejas\Models\Membro> $membros
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
        <h1 class="dash-page-title">Membros</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> membro<?= $total === 1 ? '' : 's' ?> cadastrado<?= $total === 1 ? '' : 's' ?>.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/membros/novo" class="btn-k btn-k-grad">
            <i class="bi bi-person-plus"></i> Novo membro
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/membros" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por nome ou e-mail..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/membros" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($membros === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-people"></i></div>
            <h2><?= $search !== '' ? 'Nenhum membro encontrado' : 'Nenhum membro cadastrado ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro nome ou e-mail.'
                    : 'Comece cadastrando o primeiro membro da igreja.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/membros/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-person-plus"></i> Cadastrar membro
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Contato</th>
                        <th>Idade</th>
                        <th>Membro desde</th>
                        <th>Status</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($membros as $membro): ?>
                        <tr>
                            <td>
                                <a href="<?= $basePath ?>/dashboard/membros/<?= $membro->id ?>" class="crud-person crud-person-link">
                                    <span class="crud-avatar">
                                        <?= htmlspecialchars(mb_strtoupper(mb_substr($membro->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span><?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                            </td>
                            <td>
                                <?php if ($membro->email): ?>
                                    <div><?= htmlspecialchars($membro->email, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                                <?php if ($membro->telefone): ?>
                                    <div class="crud-text-dim"><?= htmlspecialchars($membro->telefone, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                                <?php if (!$membro->email && !$membro->telefone): ?>
                                    <span class="crud-text-dim">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $membro->idade() !== null ? $membro->idade() . ' anos' : '—' ?></td>
                            <td>
                                <?= $membro->dataMembresia
                                    ? (new DateTimeImmutable($membro->dataMembresia))->format('d/m/Y')
                                    : '—' ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $membro->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $membro->status === 'ativo' ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a
                                    href="<?= $basePath ?>/dashboard/membros/<?= $membro->id ?>"
                                    class="crud-icon-btn"
                                    aria-label="Ver perfil de <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-person-lines-fill"></i>
                                </a>
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/dashboard/membros/<?= $membro->id ?>/excluir"
                                    data-confirm="Remover &quot;<?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>&quot; da lista de membros?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button
                                        type="submit"
                                        class="crud-icon-btn danger"
                                        aria-label="Excluir <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>"
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
                        href="<?= $basePath ?>/dashboard/membros?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
