<?php
/**
 * @var array $config
 * @var array<int, \Igrejas\Models\AgendaEvento> $eventos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';

$statusLabels = ['agendado' => 'Agendado', 'realizado' => 'Realizado', 'cancelado' => 'Cancelado'];
$tipoLabels = ['evento' => 'Evento', 'reuniao' => 'Reunião', 'reserva' => 'Reserva de espaço', 'outro' => 'Outro'];
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/agenda-calendario.css?v=<?= \Igrejas\Core\View::assetVersion('assets/css/agenda-calendario.css') ?>">

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Agenda</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> evento<?= $total === 1 ? '' : 's' ?> cadastrado<?= $total === 1 ? '' : 's' ?>.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/agenda/novo" class="btn-k btn-k-grad">
            <i class="bi bi-calendar-plus"></i> Novo evento
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="agenda-tabs">
    <a href="<?= $basePath ?>/dashboard/agenda" class="agenda-tab"><i class="bi bi-calendar3"></i> Calendário</a>
    <a href="<?= $basePath ?>/dashboard/agenda/lista" class="agenda-tab is-active"><i class="bi bi-list-ul"></i> Lista</a>
</div>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/agenda/lista" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por título ou local..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/agenda/lista" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($eventos === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-calendar3"></i></div>
            <h2><?= $search !== '' ? 'Nenhum evento encontrado' : 'Nenhum evento cadastrado ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro título ou local.'
                    : 'Comece cadastrando o próximo evento, reunião ou reserva de espaço.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/agenda/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-calendar-plus"></i> Cadastrar evento
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Tipo</th>
                        <th>Data</th>
                        <th>Local</th>
                        <th>Responsável</th>
                        <th>Status</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventos as $evento): ?>
                        <tr>
                            <td>
                                <div class="crud-person">
                                    <span class="crud-avatar">
                                        <i class="bi <?= $evento->ehPrivado() ? 'bi-lock-fill' : 'bi-calendar3' ?>" style="font-size: 0.95rem;"></i>
                                    </span>
                                    <span>
                                        <?= htmlspecialchars($evento->titulo, ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($evento->ehPrivado()): ?>
                                            <span class="crud-text-dim" style="font-size: 0.72rem;">(só você vê)</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </td>
                            <td><?= $tipoLabels[$evento->tipo] ?? $evento->tipo ?></td>
                            <td><?= htmlspecialchars($evento->dataHoraFormatada(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= $evento->local
                                    ? htmlspecialchars($evento->local, ENT_QUOTES, 'UTF-8')
                                    : '<span class="crud-text-dim">&mdash;</span>' ?>
                            </td>
                            <td>
                                <?= $evento->responsavelNome
                                    ? htmlspecialchars($evento->responsavelNome, ENT_QUOTES, 'UTF-8')
                                    : '<span class="crud-text-dim">&mdash;</span>' ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $evento->status === 'agendado' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $statusLabels[$evento->status] ?? $evento->status ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a
                                    href="<?= $basePath ?>/dashboard/agenda/<?= $evento->id ?>/editar"
                                    class="crud-icon-btn"
                                    aria-label="Editar <?= htmlspecialchars($evento->titulo, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/dashboard/agenda/<?= $evento->id ?>/excluir"
                                    data-confirm="Remover o evento &quot;<?= htmlspecialchars($evento->titulo, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button
                                        type="submit"
                                        class="crud-icon-btn danger"
                                        aria-label="Excluir <?= htmlspecialchars($evento->titulo, ENT_QUOTES, 'UTF-8') ?>"
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
                        href="<?= $basePath ?>/dashboard/agenda/lista?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
