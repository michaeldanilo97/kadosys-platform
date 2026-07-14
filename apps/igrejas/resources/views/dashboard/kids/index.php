<?php
/**
 * @var array $config
 * @var int $turmasAtivas
 * @var int $criancasAtivas
 * @var int $checkinsHoje
 * @var int $presentesAgora
 * @var int $conteudosPublicados
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><i class="bi bi-emoji-smile"></i> KADOSYS Kids</h1>
        <p class="dash-page-subtitle">Ministério infantil: turmas, crianças, check-in e a biblioteca de conteúdo.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/kids/biblioteca" class="btn-k btn-k-ghost">
            <i class="bi bi-stars"></i> Ver biblioteca
        </a>
        <a href="<?= $basePath ?>/dashboard/kids/checkin" class="btn-k btn-k-grad">
            <i class="bi bi-qr-code-scan"></i> Ir para o Check-in
        </a>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon blue"><i class="bi bi-collection"></i></div>
        </div>
        <div class="value"><?= $turmasAtivas ?></div>
        <div class="label">Turmas ativas</div>
        <div class="delta"><a href="<?= $basePath ?>/dashboard/kids/turmas">Ver turmas</a></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon violet"><i class="bi bi-emoji-laughing"></i></div>
        </div>
        <div class="value"><?= $criancasAtivas ?></div>
        <div class="label">Crianças cadastradas</div>
        <div class="delta"><a href="<?= $basePath ?>/dashboard/kids/criancas">Ver crianças</a></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon cyan"><i class="bi bi-door-open"></i></div>
        </div>
        <div class="value"><?= $presentesAgora ?></div>
        <div class="label">Na sala agora</div>
        <div class="delta"><a href="<?= $basePath ?>/dashboard/kids/checkin">Ver check-in</a></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon green"><i class="bi bi-check2-circle"></i></div>
        </div>
        <div class="value"><?= $checkinsHoje ?></div>
        <div class="label">Check-ins hoje</div>
        <div class="delta">Total do dia</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon blue"><i class="bi bi-stars"></i></div>
        </div>
        <div class="value"><?= $conteudosPublicados ?></div>
        <div class="label">Conteúdos na biblioteca</div>
        <div class="delta"><a href="<?= $basePath ?>/dashboard/kids/conteudos">Gerenciar conteúdos</a></div>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-head">
        <h2><i class="bi bi-grid-1x2"></i> Áreas do módulo</h2>
    </div>
    <div class="module-grid">
        <a href="<?= $basePath ?>/dashboard/kids/turmas" class="module-card">
            <div class="icon"><i class="bi bi-collection"></i></div>
            <div>
                <div class="name">Turmas</div>
                <div class="desc">Organize as salas por faixa etária e defina o professor responsável por cada uma.</div>
            </div>
            <i class="bi bi-arrow-right-short arrow"></i>
        </a>
        <a href="<?= $basePath ?>/dashboard/kids/criancas" class="module-card">
            <div class="icon"><i class="bi bi-emoji-laughing"></i></div>
            <div>
                <div class="name">Crianças</div>
                <div class="desc">Cadastro completo: responsável, autorizados a retirar, alergias e progresso (XP, moedas, sequência).</div>
            </div>
            <i class="bi bi-arrow-right-short arrow"></i>
        </a>
        <a href="<?= $basePath ?>/dashboard/kids/checkin" class="module-card">
            <div class="icon"><i class="bi bi-qr-code-scan"></i></div>
            <div>
                <div class="name">Check-in</div>
                <div class="desc">Registre entrada e saída na porta da sala, com código de segurança de 4 dígitos para a retirada.</div>
            </div>
            <i class="bi bi-arrow-right-short arrow"></i>
        </a>
        <a href="<?= $basePath ?>/dashboard/kids/conteudos" class="module-card">
            <div class="icon"><i class="bi bi-collection-play"></i></div>
            <div>
                <div class="name">Conteúdos</div>
                <div class="desc">Histórias, vídeos, devocionais, quiz e mais - biblioteca oficial KADOSYS + o que sua igreja publicar.</div>
            </div>
            <i class="bi bi-arrow-right-short arrow"></i>
        </a>
        <a href="<?= $basePath ?>/dashboard/kids/biblioteca" class="module-card">
            <div class="icon"><i class="bi bi-stars"></i></div>
            <div>
                <div class="name">Biblioteca (modo criança)</div>
                <div class="desc">A tela colorida que a criança vê, com cards grandes por tipo de conteúdo e recompensas.</div>
            </div>
            <i class="bi bi-arrow-right-short arrow"></i>
        </a>
    </div>
</div>
