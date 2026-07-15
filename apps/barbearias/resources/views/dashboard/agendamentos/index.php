<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Agendamento;
use Barbearias\Models\Unidade;

/**
 * @var array $config
 * @var array<int, Agendamento> $agendamentos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string $status
 * @var array<int, Unidade> $unidades
 * @var int $unidadeId
 * @var string|null $success
 */
$basePath = $config['base_path'] ?? '';

$statusLabel = [
    Agendamento::STATUS_AGENDADO => ['Agendado', 'info'],
    Agendamento::STATUS_CONCLUIDO => ['Concluído', 'ok'],
    Agendamento::STATUS_CANCELADO => ['Cancelado', 'danger'],
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Agenda</p>
            <h1 class="dashboard-title">Agendamentos</h1>
            <p class="dash-page-subtitle"><?= $total ?> encontrado<?= $total === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/agendamentos/novo" class="btn-k btn-k-grad">+ Novo agendamento</a>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="GET" action="<?= $basePath ?>/dashboard/agendamentos" class="crud-search">
            <input type="text" name="busca" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por cliente, profissional ou serviço...">
            <select name="status" onchange="this.form.submit()" style="padding:0.65rem 1rem; border-radius:10px; border:1px solid var(--glass-border); background:rgba(15,23,42,0.5); color:var(--text);">
                <option value="">Todos os status</option>
                <?php foreach ($statusLabel as $valor => [$label, ]): ?>
                    <option value="<?= $valor ?>" <?= $status === $valor ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($unidades !== []): ?>
                <select name="unidade" onchange="this.form.submit()" style="padding:0.65rem 1rem; border-radius:10px; border:1px solid var(--glass-border); background:rgba(15,23,42,0.5); color:var(--text);">
                    <option value="">Todas as unidades</option>
                    <?php foreach ($unidades as $unidade): ?>
                        <option value="<?= $unidade->id ?>" <?= $unidadeId === $unidade->id ? 'selected' : '' ?>><?= htmlspecialchars($unidade->nome, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <button type="submit" class="btn-k btn-k-outline">Buscar</button>
        </form>

        <?php if ($agendamentos === []): ?>
            <p class="crud-empty">Nenhum agendamento encontrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Data/hora</th>
                            <th>Cliente</th>
                            <th>Profissional</th>
                            <th>Serviço</th>
                            <?php if ($unidades !== []): ?><th>Unidade</th><?php endif; ?>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agendamentos as $agendamento): ?>
                            <?php [$label, $badge] = $statusLabel[$agendamento->status] ?? ['-', 'dim']; ?>
                            <tr>
                                <td><?= (new DateTimeImmutable($agendamento->dataHora))->format('d/m/Y H:i') ?></td>
                                <td><?= htmlspecialchars($agendamento->clienteNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($agendamento->profissionalNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($agendamento->servicoNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <?php if ($unidades !== []): ?><td class="text-dim"><?= $agendamento->unidadeNome ? htmlspecialchars($agendamento->unidadeNome, ENT_QUOTES, 'UTF-8') : '-' ?></td><?php endif; ?>
                                <td><span class="status-badge <?= $badge ?>"><?= $label ?></span></td>
                                <td class="actions-col">
                                    <?php if ($agendamento->status === Agendamento::STATUS_AGENDADO): ?>
                                        <a href="<?= $basePath ?>/dashboard/agendamentos/<?= $agendamento->id ?>/pagamento" class="crud-icon-btn" title="Concluir e registrar pagamento">✅</a>
                                        <form method="POST" action="<?= $basePath ?>/dashboard/agendamentos/<?= $agendamento->id ?>/status">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="novo_status" value="cancelado">
                                            <button type="submit" class="crud-icon-btn" title="Cancelar">🚫</button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= $basePath ?>/dashboard/agendamentos/<?= $agendamento->id ?>/editar" class="crud-icon-btn" title="Editar">✏️</a>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/agendamentos/<?= $agendamento->id ?>/excluir" onsubmit="return confirm('Excluir este agendamento?');">
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
                            <a href="<?= $basePath ?>/dashboard/agendamentos?pagina=<?= $p ?>&busca=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&unidade=<?= $unidadeId ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
