<?php

use Barbearias\Models\Agendamento;
use Barbearias\Models\User;

/**
 * @var array $config
 * @var User|null $user
 * @var \Barbearias\Models\Barbearia|null $barbearia
 * @var int $totalProfissionais
 * @var int $totalServicos
 * @var int $totalClientes
 * @var int $agendamentosHoje
 * @var array<int, Agendamento> $proximosAgendamentos
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <p class="dashboard-eyebrow">Painel</p>
    <h1 class="dashboard-title">Olá, <?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?> 👋</h1>

    <div class="kpi-grid">
        <div class="glass-card kpi-card">
            <p class="kpi-label">Agendamentos hoje</p>
            <p class="kpi-valor"><?= $agendamentosHoje ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Profissionais ativos</p>
            <p class="kpi-valor"><?= $totalProfissionais ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Serviços ativos</p>
            <p class="kpi-valor"><?= $totalServicos ?></p>
        </div>
        <div class="glass-card kpi-card">
            <p class="kpi-label">Clientes</p>
            <p class="kpi-valor"><?= $totalClientes ?></p>
        </div>
    </div>

    <div class="dash-panel glass-card">
        <div class="dash-panel-head">
            <h2>Próximos agendamentos</h2>
            <a href="<?= $basePath ?>/dashboard/agendamentos" class="btn-k btn-k-outline btn-k-sm">Ver todos</a>
        </div>

        <?php if ($proximosAgendamentos === []): ?>
            <p class="crud-empty">Nenhum agendamento futuro.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>Data/hora</th>
                            <th>Cliente</th>
                            <th>Profissional</th>
                            <th>Serviço</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proximosAgendamentos as $agendamento): ?>
                            <tr>
                                <td><?= (new DateTimeImmutable($agendamento->dataHora))->format('d/m/Y H:i') ?></td>
                                <td><?= htmlspecialchars($agendamento->clienteNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($agendamento->profissionalNome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($agendamento->servicoNome, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="modulo-grid">
        <a href="<?= $basePath ?>/dashboard/profissionais" class="glass-card modulo-card" style="display:block;">
            <div class="icone">👤</div>
            <h3>Profissionais</h3>
            <p>Cadastre a equipe da barbearia e seus horários.</p>
        </a>
        <a href="<?= $basePath ?>/dashboard/servicos" class="glass-card modulo-card" style="display:block;">
            <div class="icone">✂️</div>
            <h3>Serviços</h3>
            <p>Corte, barba, combos - com duração e preço.</p>
        </a>
        <a href="<?= $basePath ?>/dashboard/clientes" class="glass-card modulo-card" style="display:block;">
            <div class="icone">📇</div>
            <h3>Clientes</h3>
            <p>Histórico e contato de cada cliente.</p>
        </a>
        <a href="<?= $basePath ?>/dashboard/agendamentos" class="glass-card modulo-card" style="display:block;">
            <div class="icone">📅</div>
            <h3>Agendamentos</h3>
            <p>Agenda por profissional, do jeito mais simples.</p>
        </a>
    </div>
</main>
