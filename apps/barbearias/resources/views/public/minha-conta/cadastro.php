<?php

use Barbearias\Models\Barbearia;

/**
 * @var array $config
 * @var Barbearia $barbearia
 * @var string $csrf
 * @var array<int, string> $errors
 * @var array $old
 */
$basePath = $config['base_path'] ?? '';
$slug = htmlspecialchars($barbearia->slug, ENT_QUOTES, 'UTF-8');
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card">
        <div class="hero-eyebrow">Área do cliente</div>
        <h1>Criar conta</h1>
        <p class="subtitle">Acompanhe seus agendamentos na <?= htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') ?>.</p>

        <?php if ($errors !== []): ?>
            <div class="form-alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/minha-conta/<?= $slug ?>/cadastro">
            <?= $csrf ?>
            <div class="form-field">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
            </div>
            <div class="form-field-row">
                <div class="form-field">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($old['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000" required>
                    <span class="form-field-hint">Se você já agendou antes com esse telefone, seu histórico é vinculado automaticamente.</span>
                </div>
                <div class="form-field">
                    <label for="email">E-mail (opcional)</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="form-field-row">
                <div class="form-field">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" minlength="8" placeholder="Mínimo 8 caracteres" required>
                </div>
                <div class="form-field">
                    <label for="senha_confirmacao">Confirmar senha</label>
                    <input type="password" id="senha_confirmacao" name="senha_confirmacao" minlength="8" required>
                </div>
            </div>
            <button type="submit" class="btn-k btn-k-grad" style="width:100%; margin-top: 0.5rem;">Criar conta</button>
        </form>

        <p style="text-align:center; margin-top:1.5rem; font-size:0.85rem; color: var(--gray-400);">
            Já tem uma conta? <a href="<?= $basePath ?>/minha-conta/<?= $slug ?>/entrar">Entrar</a>
        </p>
    </div>
</div>
