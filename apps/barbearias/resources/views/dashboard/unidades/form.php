<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Unidade;

/**
 * @var array $config
 * @var Unidade|null $unidade
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $unidade !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/unidades/' . $unidade->id : $basePath . '/dashboard/unidades';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Estrutura</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar unidade' : 'Nova unidade' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/unidades" class="btn-k btn-k-outline">Voltar</a>
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
                    <label for="nome">Nome da unidade</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $unidade->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Unidade Centro" required autofocus>
                </div>
                <div class="form-field crud-field-full">
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($old['endereco'] ?? $unidade->endereco ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Rua, número, bairro">
                </div>
                <div class="form-field">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($old['cidade'] ?? $unidade->cidade ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="estado">Estado (UF)</label>
                    <input type="text" id="estado" name="estado" maxlength="2" value="<?= htmlspecialchars($old['estado'] ?? $unidade->estado ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="SP">
                </div>
                <div class="form-field">
                    <label for="cep">CEP</label>
                    <input type="text" id="cep" name="cep" value="<?= htmlspecialchars($old['cep'] ?? $unidade->cep ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="00000-000">
                </div>
                <div class="form-field">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($old['telefone'] ?? $unidade->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 0000-0000">
                </div>
                <div class="form-field">
                    <label for="whatsapp">WhatsApp</label>
                    <input type="text" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars($old['whatsapp'] ?? $unidade->whatsapp ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
                <?php if ($isEdit && !$unidade->principal): ?>
                    <div class="form-field crud-checkbox-field">
                        <input type="checkbox" id="ativa" name="ativa" value="1" <?= $unidade->ativa ? 'checked' : '' ?>>
                        <label for="ativa" style="margin:0;">Unidade ativa</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/unidades" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
