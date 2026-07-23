<?php

use Food\Core\Csrf;
use Food\Models\Cliente;

/**
 * @var array $config
 * @var Cliente|null $cliente
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $cliente !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/clientes/' . $cliente->id : $basePath . '/dashboard/clientes';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Vendas</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar cliente' : 'Novo cliente' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/clientes" class="btn-k btn-k-outline">Voltar</a>
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
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $cliente->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <div class="form-field">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($old['telefone'] ?? $cliente->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
                <div class="form-field">
                    <label for="whatsapp">WhatsApp</label>
                    <input type="text" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars($old['whatsapp'] ?? $cliente->whatsapp ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
                <div class="form-field">
                    <label for="aniversario">Aniversário</label>
                    <input type="date" id="aniversario" name="aniversario" value="<?= htmlspecialchars($old['aniversario'] ?? $cliente->aniversario ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <?php if ($isEdit): ?>
                    <div class="form-field crud-checkbox-field">
                        <input type="checkbox" id="ativo" name="ativo" value="1" <?= $cliente->ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;">Cliente ativo</label>
                    </div>
                <?php endif; ?>
                <div class="form-field crud-field-full">
                    <label for="endereco">Endereço</label>
                    <textarea id="endereco" name="endereco" rows="2"><?= htmlspecialchars($old['endereco'] ?? $cliente->endereco ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="form-field crud-field-full">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="2"><?= htmlspecialchars($old['observacoes'] ?? $cliente->observacoes ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/clientes" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
