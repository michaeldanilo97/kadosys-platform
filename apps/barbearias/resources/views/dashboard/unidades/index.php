<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Unidade;

/**
 * @var array $config
 * @var array<int, Unidade> $unidades
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Estrutura</p>
            <h1 class="dashboard-title">Unidades</h1>
            <p class="dash-page-subtitle"><?= count($unidades) ?> cadastrada<?= count($unidades) === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/unidades/nova" class="btn-k btn-k-grad">+ Nova unidade</a>
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

    <?php if (count($unidades) === 1): ?>
        <p class="crud-empty" style="text-align:left; padding: 0 0 1.5rem;">
            Sua barbearia tem uma unidade só - cadastre uma segunda pra habilitar a escolha de unidade
            nos agendamentos e na agenda do painel.
        </p>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <?php if ($unidades === []): ?>
            <p class="crud-empty">Nenhuma unidade encontrada.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Cidade/UF</th>
                            <th>Telefone</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unidades as $unidade): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($unidade->nome, ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($unidade->principal): ?>
                                        <span class="status-badge info" style="margin-left:0.4rem;">Principal</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-dim">
                                    <?= $unidade->cidade ? htmlspecialchars($unidade->cidade, ENT_QUOTES, 'UTF-8') : '-' ?><?= $unidade->estado ? '/' . htmlspecialchars($unidade->estado, ENT_QUOTES, 'UTF-8') : '' ?>
                                </td>
                                <td class="text-dim"><?= $unidade->telefone ? htmlspecialchars($unidade->telefone, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                <td>
                                    <span class="status-badge <?= $unidade->ativa ? 'ok' : 'dim' ?>"><?= $unidade->ativa ? 'Ativa' : 'Inativa' ?></span>
                                </td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/unidades/<?= $unidade->id ?>/editar" class="crud-icon-btn" title="Editar">✏️</a>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/unidades/<?= $unidade->id ?>/excluir" onsubmit="return confirm('Excluir esta unidade?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Excluir">🗑️</button>
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
