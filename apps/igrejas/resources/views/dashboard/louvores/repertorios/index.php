<?php

/**
 * @var array $config
 * @var array<int, \Igrejas\Models\Repertorio> $repertorios
 * @var bool $ehLider
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';

$statusLabels = ['planejado' => 'Planejado', 'encerrado' => 'Encerrado'];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Programação de Culto</h1>
        <p class="dash-page-subtitle">
            Monte a ordem dos louvores do culto e acompanhe ao vivo no Modo Culto.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/louvores" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Louvores
        </a>
        <?php if ($ehLider): ?>
            <a href="<?= $basePath ?>/dashboard/louvores/repertorios/novo" class="btn-k btn-k-grad">
                <i class="bi bi-plus-lg"></i> Novo repertório
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!$ehLider): ?>
    <div class="crud-alert" style="background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.3); color: var(--primary-soft);">
        <i class="bi bi-info-circle"></i>
        Só o líder de louvor monta/reordena o repertório - você pode acompanhar qualquer um deles no Modo Culto.
    </div>
<?php endif; ?>

<div class="dash-panel">
    <?php if ($repertorios === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-music-note-list"></i></div>
            <h2>Nenhum repertório cadastrado ainda</h2>
            <p><?= $ehLider ? 'Comece criando o repertório do próximo culto.' : 'Peça pro líder de louvor criar o repertório do próximo culto.' ?></p>
            <?php if ($ehLider): ?>
                <a href="<?= $basePath ?>/dashboard/louvores/repertorios/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-plus-lg"></i> Novo repertório
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Repertório</th>
                        <th>Louvores</th>
                        <th>Status</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($repertorios as $repertorio): ?>
                        <tr>
                            <td>
                                <div class="crud-person">
                                    <span class="crud-avatar"><i class="bi bi-music-note-list" style="font-size: 0.95rem;"></i></span>
                                    <span><?= htmlspecialchars($repertorio->titulo, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td><?= count($repertorio->itens) ?: '—' ?></td>
                            <td>
                                <span class="status-badge <?= $repertorio->status === 'planejado' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $statusLabels[$repertorio->status] ?? $repertorio->status ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <a
                                    href="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/culto"
                                    class="crud-icon-btn"
                                    aria-label="Abrir Modo Culto de <?= htmlspecialchars($repertorio->titulo, ENT_QUOTES, 'UTF-8') ?>"
                                    title="Modo Culto"
                                >
                                    <i class="bi bi-broadcast"></i>
                                </a>
                                <?php if ($ehLider): ?>
                                    <a
                                        href="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/editar"
                                        class="crud-icon-btn"
                                        aria-label="Editar <?= htmlspecialchars($repertorio->titulo, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($repertorio->status === 'planejado'): ?>
                                        <form
                                            method="POST"
                                            action="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/encerrar"
                                            data-confirm="Encerrar o repertório &quot;<?= htmlspecialchars($repertorio->titulo, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                                        >
                                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="crud-icon-btn danger" aria-label="Encerrar" title="Encerrar">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
