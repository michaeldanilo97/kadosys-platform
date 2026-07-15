<?php

use Barbearias\Models\Plano;

/**
 * @var array $config
 * @var array<int, string> $planos
 * @var int $trialDias
 */
$basePath = $config['base_path'] ?? '';

$recursos = [
    ['icone' => '📅', 'titulo' => 'Agendamentos', 'texto' => 'Agenda por profissional, com lembrete automático pro cliente antes do horário.'],
    ['icone' => '👤', 'titulo' => 'Profissionais', 'texto' => 'Cadastre a equipe, defina horários e acompanhe a produtividade de cada um.'],
    ['icone' => '✂️', 'titulo' => 'Serviços', 'texto' => 'Corte, barba, combos - com duração e preço configuráveis.'],
    ['icone' => '📇', 'titulo' => 'Clientes', 'texto' => 'Histórico completo de cada cliente, sempre à mão na hora de atender.'],
];

$planosFeatures = [
    Plano::ESSENCIAL => [
        'Até 1 profissional',
        'Agendamentos ilimitados',
        'Cadastro de clientes',
        'Suporte por WhatsApp',
    ],
    Plano::PREMIUM => [
        'Até 5 profissionais',
        'Tudo do Essencial',
        'Lembretes automáticos por WhatsApp',
        'Relatórios de faturamento',
    ],
    Plano::ENTERPRISE => [
        'Profissionais ilimitados',
        'Tudo do Plus',
        'Múltiplas unidades',
        'Suporte prioritário',
    ],
];
?>
<section class="hero">
    <span class="hero-eyebrow">Feito para barbearias de todos os tamanhos</span>
    <h1>Sua barbearia <span class="text-gradient">organizada</span>, do agendamento ao cliente fidelizado</h1>
    <p class="lead">Agenda, profissionais, serviços e clientes num só painel. Comece grátis por <?= (int) $trialDias ?> dias, sem cartão de crédito.</p>
    <div class="hero-cta">
        <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-grad">Testar grátis por <?= (int) $trialDias ?> dias</a>
        <a href="#planos" class="btn-k btn-k-outline">Ver planos</a>
    </div>
</section>

<section class="site-section" id="recursos">
    <div class="site-section-header">
        <h2>O que você vai encontrar</h2>
        <p>Tudo pensado pro dia a dia de uma barbearia, sem complicação.</p>
    </div>
    <div class="recursos-grid">
        <?php foreach ($recursos as $recurso): ?>
            <div class="glass-card recurso-card">
                <div class="icone"><?= $recurso['icone'] ?></div>
                <h3><?= htmlspecialchars($recurso['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($recurso['texto'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="site-section" id="planos">
    <div class="site-section-header">
        <h2>Planos que cabem no seu bolso</h2>
        <p>Todos com <?= (int) $trialDias ?> dias grátis pra testar antes de decidir. Cancele quando quiser.</p>
    </div>
    <div class="planos-grid">
        <?php foreach ($planos as $plano): ?>
            <?php $destaque = $plano === Plano::PREMIUM; ?>
            <div class="glass-card plano-card<?= $destaque ? ' destaque' : '' ?>">
                <?php if ($destaque): ?><span class="selo-destaque">Mais escolhido</span><?php endif; ?>
                <h3><?= htmlspecialchars(Plano::label($plano), ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="plano-preco">
                    R$ <?= number_format(Plano::valorMensal($plano), 2, ',', '.') ?>
                    <small>/mês</small>
                </p>
                <p class="plano-trial-nota"><?= (int) $trialDias ?> dias grátis, depois cobrança mensal</p>
                <ul class="plano-features">
                    <?php foreach ($planosFeatures[$plano] as $feature): ?>
                        <li><i>✓</i> <?= htmlspecialchars($feature, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= $basePath ?>/cadastro?plano=<?= urlencode($plano) ?>" class="btn-k btn-k-grad">Começar agora</a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="cta-final">
    <h2>Pronto pra organizar sua barbearia?</h2>
    <p>Comece grátis hoje - sem cartão, sem compromisso.</p>
    <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-grad">Testar grátis por <?= (int) $trialDias ?> dias</a>
</div>
