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
 * @var int $checkinsAgora
 * @var float $receitaHoje
 * @var array<int, array{aluno: \Academias\Models\Aluno, checkinsNoMes: int}> $rankingMes
 * @var int $totalAlunosRisco
 * @var \Academias\Models\AcademiaAviso|null $avisoPlataforma
 */
$basePath = $config['base_path'] ?? '';

$modulos = [
    ['href' => '/dashboard/checkin', 'icone' => 'bi-qr-code-scan', 'nome' => 'Check-in', 'desc' => 'QR fixo da entrada e quem está na academia agora.'],
    ['href' => '/dashboard/ranking', 'icone' => 'bi-trophy-fill', 'nome' => 'Ranking', 'desc' => 'Frequência do mês e streak de cada aluno.'],
    ['href' => '/dashboard/alunos', 'icone' => 'bi-people-fill', 'nome' => 'Alunos', 'desc' => 'Cadastro, matrícula e status de cada aluno.'],
    ['href' => '/dashboard/fichas-treino', 'icone' => 'bi-clipboard2-pulse-fill', 'nome' => 'Fichas de Treino', 'desc' => 'Exercícios, cargas e evolução por aluno.'],
    ['href' => '/dashboard/avaliacoes-fisicas', 'icone' => 'bi-rulers', 'nome' => 'Avaliação Física', 'desc' => 'Peso, medidas e % de gordura ao longo do tempo.'],
    ['href' => '/dashboard/professores', 'icone' => 'bi-person-badge-fill', 'nome' => 'Professores', 'desc' => 'Equipe de professores e personal trainers.'],
    ['href' => '/dashboard/planos-matricula', 'icone' => 'bi-box-seam-fill', 'nome' => 'Planos de Matrícula', 'desc' => 'Mensal, trimestral, anual - preço e duração.'],
    ['href' => '/dashboard/financeiro', 'icone' => 'bi-cash-coin', 'nome' => 'Financeiro', 'desc' => 'Caixa, mensalidades e despesas.'],
    ['href' => '/dashboard/faturas', 'icone' => 'bi-receipt', 'nome' => 'Faturas', 'desc' => 'Cobranças da sua assinatura com a KADOSYS.'],
    ['href' => '/dashboard/configuracoes', 'icone' => 'bi-gear-fill', 'nome' => 'Configurações', 'desc' => 'Perfil, marca, equipe e chave Pix.'],
];
?>
<main class="dashboard-main">
    <p class="dashboard-eyebrow">Painel</p>
    <h1 class="dashboard-title">Olá, <?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?> 👋</h1>

    <div class="kpi-grid">
        <a href="<?= $basePath ?>/dashboard/checkin" class="glass-card kpi-card">
            <div class="kpi-top">
                <span class="kpi-icon green kpi-icon-live"><i class="bi bi-qr-code-scan"></i></span>
                <span class="kpi-trend">agora</span>
            </div>
            <p class="kpi-label">Na academia agora</p>
            <p class="kpi-valor"><?= $checkinsAgora ?></p>
            <span class="kpi-link">Ver check-ins <i class="bi bi-arrow-right"></i></span>
        </a>
        <a href="<?= $basePath ?>/dashboard/financeiro" class="glass-card kpi-card">
            <div class="kpi-top">
                <span class="kpi-icon blue"><i class="bi bi-cash-coin"></i></span>
            </div>
            <p class="kpi-label">Receita de hoje</p>
            <p class="kpi-valor">R$ <?= number_format($receitaHoje, 2, ',', '.') ?></p>
            <span class="kpi-link">Ver financeiro <i class="bi bi-arrow-right"></i></span>
        </a>
        <a href="<?= $basePath ?>/dashboard/alunos" class="glass-card kpi-card">
            <div class="kpi-top">
                <span class="kpi-icon violet"><i class="bi bi-people-fill"></i></span>
            </div>
            <p class="kpi-label">Alunos ativos</p>
            <p class="kpi-valor"><?= $totalAlunosAtivos ?></p>
            <span class="kpi-link">de <?= $totalAlunos ?> no total <i class="bi bi-arrow-right"></i></span>
        </a>
        <a href="<?= $basePath ?>/dashboard/professores" class="glass-card kpi-card">
            <div class="kpi-top">
                <span class="kpi-icon cyan"><i class="bi bi-person-badge-fill"></i></span>
            </div>
            <p class="kpi-label">Professores ativos</p>
            <p class="kpi-valor"><?= $totalProfessores ?></p>
            <span class="kpi-link">Ver equipe <i class="bi bi-arrow-right"></i></span>
        </a>
        <a href="<?= $basePath ?>/dashboard/alunos/risco-evasao" class="glass-card kpi-card">
            <div class="kpi-top">
                <span class="kpi-icon <?= $totalAlunosRisco > 0 ? 'red' : 'green' ?>"><i class="bi bi-exclamation-triangle-fill"></i></span>
            </div>
            <p class="kpi-label">Risco de evasão</p>
            <p class="kpi-valor"><?= $totalAlunosRisco ?></p>
            <span class="kpi-link">Ver alunos sumidos <i class="bi bi-arrow-right"></i></span>
        </a>
    </div>

    <div class="dash-aviso-panel">
        <?php if ($avisoPlataforma !== null): ?>
            <div class="ai-insight">
                <span class="glyph"><i class="bi bi-megaphone-fill"></i></span>
                <div>
                    <strong>Aviso da plataforma</strong>
                    <p><?= htmlspecialchars($avisoPlataforma->mensagem, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="ai-insight">
                <span class="glyph"><i class="bi bi-emoji-smile"></i></span>
                <div>
                    <strong>Tudo em dia</strong>
                    <p>Nenhum aviso da plataforma no momento. Bons treinos!</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($rankingMes)): ?>
            <div class="ai-insight">
                <span class="glyph"><i class="bi bi-trophy-fill"></i></span>
                <div>
                    <strong>Top frequência do mês</strong>
                    <p>
                        <?php foreach ($rankingMes as $i => $linha): ?>
                            <?= $i > 0 ? ' · ' : '' ?><?= htmlspecialchars($linha['aluno']->nome, ENT_QUOTES, 'UTF-8') ?> (<?= $linha['checkinsNoMes'] ?>)
                        <?php endforeach; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="quick-actions">
        <a href="<?= $basePath ?>/dashboard/alunos/novo" class="quick-action">
            <i class="bi bi-person-plus-fill"></i> Novo aluno
        </a>
        <a href="<?= $basePath ?>/dashboard/checkin/qr" class="quick-action">
            <i class="bi bi-qr-code"></i> Abrir QR de check-in
        </a>
        <a href="<?= $basePath ?>/dashboard/avaliacoes-fisicas/novo" class="quick-action">
            <i class="bi bi-rulers"></i> Nova avaliação física
        </a>
        <a href="<?= $basePath ?>/dashboard/financeiro" class="quick-action">
            <i class="bi bi-plus-circle"></i> Lançar no financeiro
        </a>
    </div>

    <div class="modulo-grid">
        <?php foreach ($modulos as $modulo): ?>
            <a href="<?= $basePath . $modulo['href'] ?>" class="glass-card modulo-card">
                <div class="icone"><i class="bi <?= $modulo['icone'] ?>"></i></div>
                <h3><?= htmlspecialchars($modulo['nome'], ENT_QUOTES, 'UTF-8') ?> <span class="arrow"><i class="bi bi-arrow-right"></i></span></h3>
                <p><?= htmlspecialchars($modulo['desc'], ENT_QUOTES, 'UTF-8') ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</main>
