<?php
/**
 * @var array $config
 * @var array<int, \Igrejas\Models\Culto> $cultos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';

$statusLabels = ['agendado' => 'Agendado', 'realizado' => 'Realizado', 'cancelado' => 'Cancelado'];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Cultos</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> culto<?= $total === 1 ? '' : 's' ?> cadastrado<?= $total === 1 ? '' : 's' ?>.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/cultos/checkin-qr" class="btn-k btn-k-ghost">
            <i class="bi bi-qr-code"></i> QR de check-in
        </a>
        <a href="<?= $basePath ?>/dashboard/cultos/novo" class="btn-k btn-k-grad">
            <i class="bi bi-calendar-plus"></i> Novo culto
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/cultos" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por título ou local..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/cultos" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($cultos === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-calendar2-week"></i></div>
            <h2><?= $search !== '' ? 'Nenhum culto encontrado' : 'Nenhum culto cadastrado ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro título ou local.'
                    : 'Comece cadastrando o próximo culto da igreja.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/cultos/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-calendar-plus"></i> Cadastrar culto
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Culto</th>
                        <th>Data</th>
                        <th>Local</th>
                        <th>Presentes</th>
                        <th>Status</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cultos as $culto): ?>
                        <tr>
                            <td>
                                <div class="crud-person">
                                    <span class="crud-avatar">
                                        <i class="bi bi-calendar2-week" style="font-size: 0.95rem;"></i>
                                    </span>
                                    <span><?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($culto->dataHoraFormatada(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= $culto->local
                                    ? htmlspecialchars($culto->local, ENT_QUOTES, 'UTF-8')
                                    : '<span class="crud-text-dim">&mdash;</span>' ?>
                            </td>
                            <td><?= $culto->totalPresentes ?></td>
                            <td>
                                <span class="status-badge <?= $culto->status === 'agendado' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $statusLabels[$culto->status] ?? $culto->status ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a
                                    href="<?= $basePath ?>/dashboard/cultos/<?= $culto->id ?>/editar"
                                    class="crud-icon-btn"
                                    aria-label="Editar <?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/dashboard/cultos/<?= $culto->id ?>/excluir"
                                    data-confirm="Remover o culto &quot;<?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button
                                        type="submit"
                                        class="crud-icon-btn danger"
                                        aria-label="Excluir <?= htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') ?>"
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
                        href="<?= $basePath ?>/dashboard/cultos?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
