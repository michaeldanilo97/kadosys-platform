<?php

use Barbearias\Models\User;

/**
 * @var array $config
 * @var User|null $user
 * @var \Barbearias\Models\Barbearia|null $barbearia
 */
?>
<main class="dashboard-main">
    <p class="dashboard-eyebrow">Painel</p>
    <h1 class="dashboard-title">Olá, <?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?> 👋</h1>

    <div class="modulo-grid">
        <div class="glass-card modulo-card">
            <span class="badge-em-breve">Em breve</span>
            <div class="icone">👤</div>
            <h3>Profissionais</h3>
            <p>Cadastre a equipe da barbearia e seus horários.</p>
        </div>
        <div class="glass-card modulo-card">
            <span class="badge-em-breve">Em breve</span>
            <div class="icone">✂️</div>
            <h3>Serviços</h3>
            <p>Corte, barba, combos - com duração e preço.</p>
        </div>
        <div class="glass-card modulo-card">
            <span class="badge-em-breve">Em breve</span>
            <div class="icone">📇</div>
            <h3>Clientes</h3>
            <p>Histórico e contato de cada cliente.</p>
        </div>
        <div class="glass-card modulo-card">
            <span class="badge-em-breve">Em breve</span>
            <div class="icone">📅</div>
            <h3>Agendamentos</h3>
            <p>Agenda por profissional, com lembrete automático.</p>
        </div>
    </div>
</main>
