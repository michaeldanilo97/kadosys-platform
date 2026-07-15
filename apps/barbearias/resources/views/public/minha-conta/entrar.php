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
        <h1><?= htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle">Entre pra ver seus agendamentos.</p>

        <?php if ($errors !== []): ?>
            <div class="form-alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/minha-conta/<?= $slug ?>/entrar">
            <?= $csrf ?>
            <div class="form-field">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($old['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000" required autofocus>
            </div>
            <div class="form-field">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn-k btn-k-grad" style="width:100%; margin-top: 0.5rem;">Entrar</button>
        </form>

        <p style="text-align:center; margin-top:1.5rem; font-size:0.85rem; color: var(--gray-400);">
            Ainda não tem conta? <a href="<?= $basePath ?>/minha-conta/<?= $slug ?>/cadastro">Criar conta</a>
        </p>
        <p style="text-align:center; margin-top:0.5rem; font-size:0.85rem; color: var(--gray-400);">
            <a href="<?= $basePath ?>/agendar/<?= $slug ?>">Agendar sem conta</a>
        </p>
    </div>
</div>
