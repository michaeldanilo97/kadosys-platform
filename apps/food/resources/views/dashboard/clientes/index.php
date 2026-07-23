<?php

use Food\Core\Csrf;
use Food\Models\Cliente;

/**
 * @var array $config
 * @var array<int, Cliente> $clientes
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Vendas</p>
            <h1 class="dashboard-title">Clientes</h1>
            <p class="dash-page-subtitle"><?= $total ?> cadastrado<?= $total === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/clientes/novo" class="btn-k btn-k-grad">+ Novo cliente</a>
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
        <form method="GET" action="<?= $basePath ?>/dashboard/clientes" class="crud-search">
            <input type="text" name="busca" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome ou telefone...">
            <button type="submit" class="btn-k btn-k-outline">Buscar</button>
        </form>

        <?php if ($clientes === []): ?>
            <p class="crud-empty">Nenhum cliente encontrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Telefone/WhatsApp</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= htmlspecialchars($cliente->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($cliente->whatsapp ?? $cliente->telefone ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="status-badge <?= $cliente->ativo ? 'ok' : 'dim' ?>"><?= $cliente->ativo ? 'Ativo' : 'Inativo' ?></span></td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/clientes/<?= $cliente->id ?>" class="crud-icon-btn" title="Ver detalhes"><i class="bi bi-eye-fill"></i></a>
                                    <a href="<?= $basePath ?>/dashboard/clientes/<?= $cliente->id ?>/editar" class="crud-icon-btn" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/clientes/<?= $cliente->id ?>/excluir" data-confirm="Excluir este cliente?">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Excluir"><i class="bi bi-trash-fill"></i></button>
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
                            <a href="<?= $basePath ?>/dashboard/clientes?pagina=<?= $p ?>&busca=<?= urlencode($search) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
