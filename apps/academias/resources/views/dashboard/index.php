<?php

use Academias\Models\User;

/**
 * @var array $config
 * @var User|null $user
 * @var \Academias\Models\Academia|null $academia
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <p class="dashboard-eyebrow">Painel</p>
    <h1 class="dashboard-title">Olá, <?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?> 👋</h1>

    <div class="dash-panel glass-card">
        <div class="dash-panel-head">
            <h2>Bem-vindo(a) à KADOSYS Academias</h2>
        </div>
        <p class="crud-text-dim">
            A conta de <strong><?= htmlspecialchars($academia->nome ?? '', ENT_QUOTES, 'UTF-8') ?></strong> está pronta.
            Os módulos de alunos, check-in, ficha de treino e avaliação física chegam nos próximos ajustes.
        </p>
    </div>

    <div class="modulo-grid">
        <a href="<?= $basePath ?>/dashboard/faturas" class="glass-card modulo-card" style="display:block;">
            <div class="icone">🧾</div>
            <h3>Faturas</h3>
            <p>Histórico de cobrança da sua assinatura com a Kadosys.</p>
        </a>
    </div>
</main>
