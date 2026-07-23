<?php

use Food\Core\Csrf;
use Food\Models\CentroCusto;

/**
 * @var array $config
 * @var array<int, CentroCusto> $centros
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Financeiro</p>
            <h1 class="dashboard-title">Centros de Custo</h1>
            <p class="dash-page-subtitle"><?= count($centros) ?> cadastrado<?= count($centros) === 1 ? '' : 's' ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/financeiro" class="btn-k btn-k-outline">Voltar ao Financeiro</a>
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
        <div class="dash-panel-head">
            <h2>Novo centro de custo</h2>
        </div>
        <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/centros-custo" class="crud-form-grid">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" placeholder="Ex.: Cozinha, Delivery, Administrativo..." required>
            </div>
            <div class="form-field" style="align-self: flex-end;">
                <button type="submit" class="btn-k btn-k-grad">+ Cadastrar</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <?php if ($centros === []): ?>
            <p class="crud-empty">Nenhum centro de custo cadastrado.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Centro de custo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($centros as $centro): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                                        <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/centros-custo/<?= $centro->id ?>" style="display: flex; align-items: center; gap: 0.75rem; flex: 1 1 auto;">
                                            <?= Csrf::field() ?>
                                            <input type="text" name="nome" value="<?= htmlspecialchars($centro->nome, ENT_QUOTES, 'UTF-8') ?>" required style="flex: 1 1 auto;">
                                            <label class="crud-checkbox-field" style="margin: 0;">
                                                <input type="checkbox" name="ativo" value="1" <?= $centro->ativo ? 'checked' : '' ?>>
                                                Ativo
                                            </label>
                                            <button type="submit" class="btn-k btn-k-outline btn-k-sm">Salvar</button>
                                        </form>
                                        <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/centros-custo/<?= $centro->id ?>/excluir" data-confirm="Excluir este centro de custo?">
                                            <?= Csrf::field() ?>
                                            <button type="submit" class="crud-icon-btn danger" title="Excluir"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
