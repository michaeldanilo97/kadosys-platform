<?php

use Academias\Core\Csrf;
use Academias\Models\Aluno;

/**
 * @var array $config
 * @var array<int, Aluno> $alunos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 */
$basePath = $config['base_path'] ?? '';

$statusLabel = [
    Aluno::STATUS_ATIVO => 'Ativo',
    Aluno::STATUS_INATIVO => 'Inativo',
    Aluno::STATUS_SUSPENSO => 'Suspenso',
];

$statusBadge = [
    Aluno::STATUS_ATIVO => 'ok',
    Aluno::STATUS_INATIVO => 'dim',
    Aluno::STATUS_SUSPENSO => 'danger',
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Carteira</p>
            <h1 class="dashboard-title">Alunos</h1>
            <p class="dash-page-subtitle"><?= $total ?> matriculado<?= $total === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/alunos/novo" class="btn-k btn-k-grad">+ Novo aluno</a>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="GET" action="<?= $basePath ?>/dashboard/alunos" class="crud-search">
            <input type="text" name="busca" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome, telefone, e-mail ou CPF...">
            <button type="submit" class="btn-k btn-k-outline">Buscar</button>
        </form>

        <?php if ($alunos === []): ?>
            <p class="crud-empty">Nenhum aluno encontrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Matrícula vence</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alunos as $aluno): ?>
                            <tr>
                                <td><?= htmlspecialchars($aluno->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($aluno->telefone ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $aluno->matriculaVencimento ? (new DateTimeImmutable($aluno->matriculaVencimento))->format('d/m/Y') : '-' ?></td>
                                <td><span class="status-badge <?= $statusBadge[$aluno->status] ?? 'dim' ?>"><?= $statusLabel[$aluno->status] ?? $aluno->status ?></span></td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/alunos/<?= $aluno->id ?>/editar" class="crud-icon-btn" title="Editar">✏️</a>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/alunos/<?= $aluno->id ?>/excluir" onsubmit="return confirm('Excluir este aluno?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Excluir">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($lastPage > 1): ?>
                <div class="crud-pagination">
                    <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                        <?php if ($p === $page): ?>
                            <span class="atual"><?= $p ?></span>
                        <?php else: ?>
                            <a href="<?= $basePath ?>/dashboard/alunos?pagina=<?= $p ?>&busca=<?= urlencode($search) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
