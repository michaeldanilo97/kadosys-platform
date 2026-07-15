<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\ListaEspera;

/**
 * @var array $config
 * @var array<int, ListaEspera> $entradas
 * @var string|null $success
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Agenda</p>
            <h1 class="dashboard-title">Lista de espera</h1>
            <p class="dash-page-subtitle">Clientes aguardando um horário abrir. Sem notificação automática - entre em contato quando um horário abrir.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <?php if ($entradas === []): ?>
            <p class="crud-empty">Ninguém na lista de espera no momento.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Contato</th>
                            <th>Profissional</th>
                            <th>Serviço</th>
                            <th>Data desejada</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entradas as $entrada): ?>
                            <tr>
                                <td><?= htmlspecialchars($entrada->clienteNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $entrada->clienteTelefone ? htmlspecialchars($entrada->clienteTelefone, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                <td class="text-dim"><?= htmlspecialchars($entrada->profissionalNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($entrada->servicoNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (new DateTimeImmutable($entrada->dataDesejada))->format('d/m/Y') ?></td>
                                <td class="actions-col">
                                    <form method="POST" action="<?= $basePath ?>/dashboard/lista-espera/<?= $entrada->id ?>/atender">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn" title="Marcar como atendido">✅</button>
                                    </form>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/lista-espera/<?= $entrada->id ?>/cancelar">
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
