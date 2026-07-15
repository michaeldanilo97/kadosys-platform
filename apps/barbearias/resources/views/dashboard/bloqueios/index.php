<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\BloqueioAgenda;

/**
 * @var array $config
 * @var array<int, BloqueioAgenda> $bloqueios
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';

$labelTipo = [
    BloqueioAgenda::TIPO_BLOQUEIO => ['Bloqueio', 'dim'],
    BloqueioAgenda::TIPO_FERIAS => ['Férias', 'info'],
    BloqueioAgenda::TIPO_FOLGA => ['Folga', 'info'],
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Agenda</p>
            <h1 class="dashboard-title">Bloqueios de agenda</h1>
            <p class="dash-page-subtitle">Férias, folgas e compromissos que tiram um profissional da agenda.</p>
        </div>
        <a href="<?= $basePath ?>/dashboard/bloqueios/novo" class="btn-k btn-k-grad">+ Novo bloqueio</a>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <?php if ($bloqueios === []): ?>
            <p class="crud-empty">Nenhum bloqueio futuro cadastrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Profissional</th>
                            <th>Tipo</th>
                            <th>Início</th>
                            <th>Fim</th>
                            <th>Motivo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bloqueios as $bloqueio): ?>
                            <?php [$label, $badge] = $labelTipo[$bloqueio->tipo] ?? ['-', 'dim']; ?>
                            <tr>
                                <td><?= htmlspecialchars($bloqueio->profissionalNome ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="status-badge <?= $badge ?>"><?= $label ?></span></td>
                                <td class="text-dim"><?= (new DateTimeImmutable($bloqueio->dataInicio))->format('d/m/Y H:i') ?></td>
                                <td class="text-dim"><?= (new DateTimeImmutable($bloqueio->dataFim))->format('d/m/Y H:i') ?></td>
                                <td class="text-dim"><?= $bloqueio->motivo ? htmlspecialchars($bloqueio->motivo, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                <td class="actions-col">
                                    <form method="POST" action="<?= $basePath ?>/dashboard/bloqueios/<?= $bloqueio->id ?>/excluir" onsubmit="return confirm('Remover este bloqueio?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Remover">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
