<?php

use Academias\Core\Csrf;
use Academias\Models\PlanoMatricula;

/**
 * @var array $config
 * @var PlanoMatricula|null $plano
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $plano !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/planos-matricula/' . $plano->id : $basePath . '/dashboard/planos-matricula';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Catálogo</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar plano de matrícula' : 'Novo plano de matrícula' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/planos-matricula" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="POST" action="<?= $actionUrl ?>">
            <?= Csrf::field() ?>

            <div class="crud-form-grid">
                <div class="form-field crud-field-full">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $plano->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Mensal, Trimestral, Anual..." required autofocus>
                </div>
                <div class="form-field">
                    <label for="preco">Preço (R$)</label>
                    <input type="text" id="preco" name="preco" value="<?= htmlspecialchars($old['preco'] ?? ($plano !== null ? number_format($plano->preco, 2, ',', '.') : ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="99,90" required>
                </div>
                <div class="form-field">
                    <label for="duracao_dias">Duração (dias)</label>
                    <input type="number" id="duracao_dias" name="duracao_dias" value="<?= htmlspecialchars($old['duracao_dias'] ?? (string) ($plano->duracaoDias ?? 30), ENT_QUOTES, 'UTF-8') ?>" min="1" required>
                </div>
                <div class="form-field crud-field-full">
                    <label for="descricao">Descrição (opcional)</label>
                    <textarea id="descricao" name="descricao" rows="3" placeholder="O que está incluso neste plano..."><?= htmlspecialchars($old['descricao'] ?? $plano->descricao ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <?php if ($isEdit): ?>
                    <div class="form-field crud-checkbox-field">
                        <input type="checkbox" id="ativo" name="ativo" value="1" <?= $plano->ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;">Plano ativo</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/planos-matricula" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
