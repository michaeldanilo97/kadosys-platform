<?php
/**
 * @var array $config
 * @var array<int, \Igrejas\Models\Grupo> $grupos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';

$tipoLabels = ['celula' => 'Célula', 'classe' => 'Classe', 'grupo' => 'Grupo'];
$diaLabels = [
    'domingo' => 'Domingo', 'segunda' => 'Segunda', 'terca' => 'Terça',
    'quarta' => 'Quarta', 'quinta' => 'Quinta', 'sexta' => 'Sexta', 'sabado' => 'Sábado',
];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Grupos</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> grupo<?= $total === 1 ? '' : 's' ?> cadastrado<?= $total === 1 ? '' : 's' ?>.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/grupos/novo" class="btn-k btn-k-grad">
            <i class="bi bi-people-fill"></i> Novo grupo
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/grupos" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por nome do grupo..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/grupos" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($grupos === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-people-fill"></i></div>
            <h2><?= $search !== '' ? 'Nenhum grupo encontrado' : 'Nenhum grupo cadastrado ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro nome.'
                    : 'Comece cadastrando o primeiro pequeno grupo, célula ou classe da igreja.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/grupos/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-people-fill"></i> Cadastrar grupo
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Tipo</th>
                        <th>Líder</th>
                        <th>Encontro</th>
                        <th>Participantes</th>
                        <th>Status</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grupos as $grupo): ?>
                        <tr>
                            <td>
                                <div class="crud-person">
                                    <span class="crud-avatar">
                                        <?= htmlspecialchars(mb_strtoupper(mb_substr($grupo->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span><?= htmlspecialchars($grupo->nome, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td><?= $tipoLabels[$grupo->tipo] ?? $grupo->tipo ?></td>
                            <td>
                                <?= $grupo->liderNome
                                    ? htmlspecialchars($grupo->liderNome, ENT_QUOTES, 'UTF-8')
                                    : '<span class="crud-text-dim">Sem líder definido</span>' ?>
                            </td>
                            <td>
                                <?php if ($grupo->diaSemana !== null || $grupo->local !== null): ?>
                                    <?= $grupo->diaSemana !== null ? htmlspecialchars($diaLabels[$grupo->diaSemana] ?? $grupo->diaSemana, ENT_QUOTES, 'UTF-8') : '' ?>
                                    <?= $grupo->horario !== null ? ' às ' . substr($grupo->horario, 0, 5) : '' ?>
                                    <?php if ($grupo->local !== null): ?>
                                        <div class="crud-text-dim" style="font-size: 0.78rem;"><?= htmlspecialchars($grupo->local, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="crud-text-dim">Não definido</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $grupo->totalParticipantes ?></td>
                            <td>
                                <span class="status-badge <?= $grupo->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $grupo->status === 'ativo' ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a
                                    href="<?= $basePath ?>/dashboard/grupos/<?= $grupo->id ?>/editar"
                                    class="crud-icon-btn"
                                    aria-label="Editar <?= htmlspecialchars($grupo->nome, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/dashboard/grupos/<?= $grupo->id ?>/excluir"
                                    data-confirm="Remover o grupo &quot;<?= htmlspecialchars($grupo->nome, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button
                                        type="submit"
                                        class="crud-icon-btn danger"
                                        aria-label="Excluir <?= htmlspecialchars($grupo->nome, ENT_QUOTES, 'UTF-8') ?>"
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
                        href="<?= $basePath ?>/dashboard/grupos?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
