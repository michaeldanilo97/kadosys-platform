<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Cliente;

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
            <p class="dashboard-eyebrow">Carteira</p>
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
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? $cliente->email ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="cliente@email.com">
                </div>
                <div class="form-field">
                    <label for="data_nascimento">Data de nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars($old['data_nascimento'] ?? $cliente->dataNascimento ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/clientes" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
