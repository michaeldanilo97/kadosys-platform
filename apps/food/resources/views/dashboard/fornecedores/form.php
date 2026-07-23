<?php

use Food\Core\Csrf;
use Food\Models\Fornecedor;

/**
 * @var array $config
 * @var Fornecedor|null $fornecedor
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $fornecedor !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/fornecedores/' . $fornecedor->id : $basePath . '/dashboard/fornecedores';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Compras</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar fornecedor' : 'Novo fornecedor' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/fornecedores" class="btn-k btn-k-outline">Voltar</a>
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
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $fornecedor->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <div class="form-field">
                    <label for="contato">Pessoa de contato</label>
                    <input type="text" id="contato" name="contato" value="<?= htmlspecialchars($old['contato'] ?? $fornecedor->contato ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($old['telefone'] ?? $fornecedor->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
                <div class="form-field">
                    <label for="whatsapp">WhatsApp</label>
                    <input type="text" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars($old['whatsapp'] ?? $fornecedor->whatsapp ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
                <div class="form-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? $fornecedor->email ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="fornecedor@email.com">
                </div>
                <div class="form-field">
                    <label for="prazo_dias">Prazo de entrega (dias)</label>
                    <input type="number" id="prazo_dias" name="prazo_dias" min="0" value="<?= htmlspecialchars((string) ($old['prazo_dias'] ?? $fornecedor->prazoDias ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="forma_pagamento">Forma de pagamento combinada</label>
                    <input type="text" id="forma_pagamento" name="forma_pagamento" value="<?= htmlspecialchars($old['forma_pagamento'] ?? $fornecedor->formaPagamento ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="À vista, boleto 30/60...">
                </div>
                <div class="form-field crud-field-full">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3"><?= htmlspecialchars($old['observacoes'] ?? $fornecedor->observacoes ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/fornecedores" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
