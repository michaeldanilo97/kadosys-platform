<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Servico;

/**
 * @var array $config
 * @var Servico|null $servico
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $servico !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/servicos/' . $servico->id : $basePath . '/dashboard/servicos';
$precoValor = $old['preco'] ?? ($servico !== null ? number_format($servico->preco, 2, '.', '') : '');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Catálogo</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar serviço' : 'Novo serviço' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/servicos" class="btn-k btn-k-outline">Voltar</a>
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
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $servico->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Corte masculino" required autofocus>
                </div>
                <div class="form-field">
                    <label for="duracao_minutos">Duração (minutos)</label>
                    <input type="number" id="duracao_minutos" name="duracao_minutos" min="5" max="480" step="5" value="<?= htmlspecialchars((string) ($old['duracao_minutos'] ?? $servico->duracaoMinutos ?? 30), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="preco">Preço (R$)</label>
                    <input type="text" id="preco" name="preco" inputmode="decimal" value="<?= htmlspecialchars((string) $precoValor, ENT_QUOTES, 'UTF-8') ?>" placeholder="35,00" required>
                </div>
                <?php if ($isEdit): ?>
                    <div class="form-field crud-checkbox-field">
                        <input type="checkbox" id="ativo" name="ativo" value="1" <?= $servico->ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;">Serviço ativo</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/servicos" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
