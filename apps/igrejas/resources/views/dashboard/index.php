<?php
/**
 * @var array $config
 * @var \Igrejas\Models\User|null $user
 * @var array $modules
 */
$basePath = $config['base_path'] ?? '';
?>

<h1 class="dash-page-title">Visao geral</h1>
<p class="dash-page-subtitle">Bem-vindo(a) de volta. Aqui esta um resumo da sua igreja.</p>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="label">Membros ativos</div>
        <div class="value">--</div>
        <div class="delta">Disponivel no modulo Membros</div>
    </div>
    <div class="kpi-card">
        <div class="label">Ministerios</div>
        <div class="value">--</div>
        <div class="delta">Disponivel no modulo Ministerios</div>
    </div>
    <div class="kpi-card">
        <div class="label">Proximo culto</div>
        <div class="value">--</div>
        <div class="delta">Disponivel no modulo Cultos</div>
    </div>
    <div class="kpi-card">
        <div class="label">Financeiro do mes</div>
        <div class="value">--</div>
        <div class="delta">Disponivel no modulo Financeiro</div>
    </div>
</div>

<div class="dash-panel">
    <h2>Modulos do sistema</h2>
    <div class="module-grid">
        <?php foreach ($modules as $slug => $module): ?>
            <a href="<?= $basePath ?>/dashboard/<?= $slug ?>" class="module-card">
                <div class="icon"><i class="bi <?= htmlspecialchars($module['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                <div>
                    <div class="name"><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="desc"><?= htmlspecialchars($module['description'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
