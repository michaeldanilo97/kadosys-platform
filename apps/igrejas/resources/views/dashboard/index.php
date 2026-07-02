<?php
/**
 * @var array $config
 * @var \Igrejas\Models\User|null $user
 * @var array $modules
 * @var int $membrosAtivos
 * @var int $novosMembros
 */
$basePath = $config['base_path'] ?? '';
$firstName = explode(' ', trim($user?->name ?? 'Usuario'))[0];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Ola, <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?> <span class="wave">&#128075;</span></h1>
        <p class="dash-page-subtitle">Aqui esta a visao geral da sua igreja em tempo real.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/relatorios" class="btn-k btn-k-ghost"><i class="bi bi-bar-chart-line"></i> Relatorios</a>
        <a href="<?= $basePath ?>/dashboard/membros/novo" class="btn-k btn-k-grad"><i class="bi bi-plus-lg"></i> Novo membro</a>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon blue"><i class="bi bi-people"></i></div>
            <?php if ($novosMembros > 0): ?>
                <span class="kpi-trend up"><i class="bi bi-arrow-up-short"></i> +<?= $novosMembros ?> este mes</span>
            <?php else: ?>
                <span class="kpi-trend neutral"><i class="bi bi-dash"></i> sem novidades</span>
            <?php endif; ?>
        </div>
        <div class="value"><?= $membrosAtivos ?></div>
        <div class="label">Membros ativos</div>
        <div class="delta"><a href="<?= $basePath ?>/dashboard/membros">Ver todos os membros</a></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon violet"><i class="bi bi-diagram-3"></i></div>
            <span class="kpi-trend neutral"><i class="bi bi-dash"></i> aguardando dados</span>
        </div>
        <div class="value">--</div>
        <div class="label">Ministerios</div>
        <div class="delta">Disponivel no modulo Ministerios</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon cyan"><i class="bi bi-calendar2-week"></i></div>
            <span class="kpi-trend neutral"><i class="bi bi-dash"></i> aguardando dados</span>
        </div>
        <div class="value">--</div>
        <div class="label">Proximo culto</div>
        <div class="delta">Disponivel no modulo Cultos</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon green"><i class="bi bi-cash-coin"></i></div>
            <span class="kpi-trend neutral"><i class="bi bi-dash"></i> aguardando dados</span>
        </div>
        <div class="value">--</div>
        <div class="label">Financeiro do mes</div>
        <div class="delta">Disponivel no modulo Financeiro</div>
    </div>
</div>

<div class="dash-panels-row">
    <div class="dash-panel dash-ai-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-stars"></i> Insights da IA</h2>
            <span class="panel-badge">em breve</span>
        </div>
        <div class="ai-insight-list">
            <div class="ai-insight">
                <div class="glyph"><i class="bi bi-graph-up-arrow"></i></div>
                <p>Resumos automaticos de frequencia e crescimento da congregacao.</p>
            </div>
            <div class="ai-insight">
                <div class="glyph"><i class="bi bi-cash-stack"></i></div>
                <p>Alertas inteligentes sobre dizimos, ofertas e despesas fora do padrao.</p>
            </div>
            <div class="ai-insight">
                <div class="glyph"><i class="bi bi-chat-square-text"></i></div>
                <p>Sugestoes de comunicacao para engajar membros e voluntarios.</p>
            </div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-lightning-charge"></i> Acoes rapidas</h2>
        </div>
        <div class="quick-actions">
            <a href="<?= $basePath ?>/dashboard/membros/novo" class="quick-action">
                <i class="bi bi-person-plus"></i> Cadastrar membro
            </a>
            <a href="<?= $basePath ?>/dashboard/cultos" class="quick-action">
                <i class="bi bi-calendar-plus"></i> Agendar culto
            </a>
            <a href="<?= $basePath ?>/dashboard/financeiro" class="quick-action">
                <i class="bi bi-wallet2"></i> Lancar oferta
            </a>
            <a href="<?= $basePath ?>/dashboard/comunicacao" class="quick-action">
                <i class="bi bi-megaphone"></i> Enviar aviso
            </a>
        </div>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-head">
        <h2><i class="bi bi-grid-1x2"></i> Modulos do sistema</h2>
    </div>
    <div class="module-grid">
        <?php foreach ($modules as $slug => $module): ?>
            <a href="<?= $basePath ?>/dashboard/<?= $slug ?>" class="module-card">
                <div class="icon"><i class="bi <?= htmlspecialchars($module['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                <div>
                    <div class="name"><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="desc"><?= htmlspecialchars($module['description'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <i class="bi bi-arrow-right-short arrow"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>
