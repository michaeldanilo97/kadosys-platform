<?php

use Barbearias\Models\Barbearia;
use Barbearias\Models\Profissional;

/**
 * @var array $config
 * @var Barbearia $barbearia
 * @var array<int, Profissional> $profissionais
 * @var int $aguardando
 * @var int $esperaEstimada
 * @var string $csrf
 * @var array<int, string> $errors
 * @var array $old
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card">
        <div class="hero-eyebrow">Fila de espera</div>
        <h1><?= htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle">Entre na fila sem precisar agendar horário. Avisamos quando chegar sua vez.</p>

        <div class="confirmacao-detalhes" style="margin-bottom: 1.5rem;">
            <div><span>Aguardando agora</span><span><?= $aguardando ?> <?= $aguardando === 1 ? 'pessoa' : 'pessoas' ?></span></div>
            <div><span>Espera estimada</span><span><?= $esperaEstimada > 0 ? $esperaEstimada . ' min' : 'sem espera' ?></span></div>
        </div>

        <?php if ($errors !== []): ?>
            <div class="form-alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/fila/<?= htmlspecialchars($barbearia->slug, ENT_QUOTES, 'UTF-8') ?>">
            <?= $csrf ?>

            <div class="form-field">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required autofocus value="<?= htmlspecialchars((string) ($old['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-field">
                <label for="telefone">Telefone (opcional)</label>
                <input type="text" id="telefone" name="telefone" placeholder="(11) 90000-0000" value="<?= htmlspecialchars((string) ($old['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <?php if ($profissionais !== []): ?>
                <div class="form-field">
                    <label for="profissional_id">Preferência de profissional (opcional)</label>
                    <select id="profissional_id" name="profissional_id">
                        <option value="0">Qualquer um</option>
                        <?php foreach ($profissionais as $profissional): ?>
                            <option value="<?= $profissional->id ?>" <?= (string) ($old['profissional_id'] ?? '') === (string) $profissional->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($profissional->nome, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-k btn-k-grad" style="width:100%; margin-top: 0.5rem;">Entrar na fila</button>
        </form>
    </div>
</div>
