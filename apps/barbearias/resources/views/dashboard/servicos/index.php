<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Servico;

/**
 * @var array $config
 * @var array<int, Servico> $servicos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Catálogo</p>
            <h1 class="dashboard-title">Serviços</h1>
            <p class="dash-page-subtitle"><?= $total ?> cadastrado<?= $total === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/servicos/novo" class="btn-k btn-k-grad">+ Novo serviço</a>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="GET" action="<?= $basePath ?>/dashboard/servicos" class="crud-search">
            <input type="text" name="busca" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome...">
            <button type="submit" class="btn-k btn-k-outline">Buscar</button>
        </form>

        <?php if ($servicos === []): ?>
            <p class="crud-empty">Nenhum serviço encontrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Duração</th>
                            <th>Preço</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servicos as $servico): ?>
                            <tr>
                                <td><?= htmlspecialchars($servico->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $servico->duracaoMinutos ?> min</td>
                                <td class="text-dim">R$ <?= number_format($servico->preco, 2, ',', '.') ?></td>
                                <td>
                                    <span class="status-badge <?= $servico->ativo ? 'ok' : 'dim' ?>"><?= $servico->ativo ? 'Ativo' : 'Inativo' ?></span>
                                </td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/servicos/<?= $servico->id ?>/editar" class="crud-icon-btn" title="Editar">✏️</a>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/servicos/<?= $servico->id ?>/excluir" onsubmit="return confirm('Excluir este serviço? Agendamentos ligados a ele também serão removidos.');">
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
                            <a href="<?= $basePath ?>/dashboard/servicos?pagina=<?= $p ?>&busca=<?= urlencode($search) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
