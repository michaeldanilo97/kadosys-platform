<?php

use Academias\Models\User;

/**
 * @var array $config
 * @var User|null $user
 * @var \Academias\Models\Academia|null $academia
 * @var int $totalAlunosAtivos
 * @var int $totalAlunos
 * @var int $totalProfessores
 * @var int $totalPlanosMatricula
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <p class="dashboard-eyebrow">Painel</p>
    <h1 class="dashboard-title">Olá, <?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?> 👋</h1>

    <div class="kpi-grid">
        <div class="glass-card kpi-card">
            <p class="kpi-label">Alunos ativos</p>
            <p class="kpi-valor"><?= $totalAlunosAtivos ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Total de alunos</p>
            <p class="kpi-valor"><?= $totalAlunos ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Professores ativos</p>
            <p class="kpi-valor"><?= $totalProfessores ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Planos de matrícula</p>
            <p class="kpi-valor"><?= $totalPlanosMatricula ?></p>
        </div>
    </div>

    <div class="modulo-grid">
        <a href="<?= $basePath ?>/dashboard/alunos" class="glass-card modulo-card" style="display:block;">
            <div class="icone">🏋️</div>
            <h3>Alunos</h3>
            <p>Cadastro, matrícula e status de cada aluno.</p>
        </a>
        <a href="<?= $basePath ?>/dashboard/professores" class="glass-card modulo-card" style="display:block;">
            <div class="icone">🧑‍🏫</div>
            <h3>Professores</h3>
            <p>Equipe de professores e personal trainers.</p>
        </a>
        <a href="<?= $basePath ?>/dashboard/planos-matricula" class="glass-card modulo-card" style="display:block;">
            <div class="icone">📦</div>
            <h3>Planos de Matrícula</h3>
            <p>Mensal, trimestral, anual - com preço e duração.</p>
        </a>
        <a href="<?= $basePath ?>/dashboard/financeiro" class="glass-card modulo-card" style="display:block;">
            <div class="icone">💰</div>
            <h3>Financeiro</h3>
            <p>Caixa, mensalidades e despesas.</p>
        </a>
    </div>
</main>
