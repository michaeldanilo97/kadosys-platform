<?php

use Academias\Models\Plano;

/**
 * @var array $config
 * @var array<int, string> $planos
 * @var int $trialDias
 */
$basePath = $config['base_path'] ?? '';

$recursos = [
    ['icone' => 'bi-qr-code-scan', 'titulo' => 'Check-in por QR Code', 'texto' => 'Um QR fixo na entrada. O aluno escaneia pra entrar e pra sair, sem crachá e sem catraca física.'],
    ['icone' => 'bi-clipboard2-pulse', 'titulo' => 'Ficha de treino', 'texto' => 'Professor monta a ficha; aluno marca cada exercício feito e acompanha a evolução de carga.'],
    ['icone' => 'bi-person-badge', 'titulo' => 'Alunos e matrículas', 'texto' => 'Cadastro completo, plano de matrícula, vencimento e status sempre à mão.'],
    ['icone' => 'bi-person-workspace', 'titulo' => 'Professores', 'texto' => 'Cadastre a equipe, especialidades e horários de cada professor.'],
];

$sobre = [
    ['icone' => 'bi-fire', 'titulo' => 'Aluno mais engajado', 'texto' => 'Streak de frequência e ranking do mês ajudam a reduzir a desistência nos primeiros meses.'],
    ['icone' => 'bi-columns-gap', 'titulo' => 'Tudo em um painel', 'texto' => 'Check-in, ficha de treino, avaliação física e financeiro num só lugar - sem planilha, sem caderno.'],
    ['icone' => 'bi-building', 'titulo' => 'Cresce com você', 'texto' => 'De uma sala a várias unidades, o sistema acompanha o tamanho da sua academia.'],
];

$funcionalidades = [
    ['icone' => 'bi-qr-code-scan', 'titulo' => 'Check-in/checkout por QR fixo', 'texto' => 'Um único QR na recepção resolve entrada e saída - cada leitura registra aluno, data e hora.'],
    ['icone' => 'bi-trophy', 'titulo' => 'Ranking de frequência', 'texto' => 'Streak de dias consecutivos e ranking mensal dos alunos mais assíduos, direto no painel deles.'],
    ['icone' => 'bi-graph-up-arrow', 'titulo' => 'Evolução de carga', 'texto' => 'Cada treino marcado vira um ponto no gráfico de evolução por exercício.'],
    ['icone' => 'bi-clipboard2-data', 'titulo' => 'Avaliação física', 'texto' => 'Peso, % de gordura e medidas corporais registrados periodicamente, com gráfico de evolução.'],
    ['icone' => 'bi-qr-code', 'titulo' => 'Pix na recepção', 'texto' => 'Cadastre sua chave Pix e gere QR Code na hora de receber a mensalidade, sem taxa de gateway.'],
    ['icone' => 'bi-graph-up', 'titulo' => 'Relatórios', 'texto' => 'Frequência, ocupação por horário e faturamento, sempre atualizados.'],
    ['icone' => 'bi-buildings', 'titulo' => 'Múltiplas unidades', 'texto' => 'Cadastre quantas unidades quiser, cada uma com seus próprios professores.'],
    ['icone' => 'bi-palette', 'titulo' => 'Sua marca', 'texto' => 'Logo e cor de destaque personalizadas no painel e no site da sua academia.'],
];

$beneficios = [
    'Comece a usar em minutos, sem instalar nada',
    'Seus dados, sempre disponíveis, com backup automático',
    'Suporte por WhatsApp direto com quem construiu o sistema',
    'Sem contrato de fidelidade - cancele quando quiser',
];

$faqs = [
    ['pergunta' => 'Preciso de cartão de crédito pra testar?', 'resposta' => 'Não. O trial de ' . (int) $trialDias . ' dias começa sem cartão nem qualquer cobrança - você só assina se quiser continuar depois.'],
    ['pergunta' => 'Como funciona o check-in por QR Code?', 'resposta' => 'A academia imprime (ou exibe numa tela) um único QR Code na entrada. O aluno, já logado no painel dele pelo celular, escaneia pra entrar e escaneia de novo pra sair - cada leitura fica registrada com data e hora, pra você e pro aluno.'],
    ['pergunta' => 'Como funciona o pagamento do plano?', 'resposta' => 'Você escolhe entre Pix (chave copia-e-cola, sem taxa) ou cartão de crédito recorrente via Mercado Pago. Dá pra trocar de plano ou forma de pagamento a qualquer momento na dashboard.'],
    ['pergunta' => 'Posso cancelar quando quiser?', 'resposta' => 'Sim, não tem fidelidade. Você cancela direto pela dashboard e continua usando até o fim do período já pago.'],
    ['pergunta' => 'O sistema funciona pra mais de uma unidade?', 'resposta' => 'Sim. A partir do plano Premium você cadastra quantas unidades quiser, cada uma com seus próprios professores.'],
    ['pergunta' => 'Meus alunos precisam baixar algum aplicativo?', 'resposta' => 'Não. O painel do aluno funciona direto no navegador do celular - inclusive dá pra "instalar" a página na tela inicial, sem passar por loja de aplicativo.'],
];

?>
<section class="hero">
    <div class="hero-inner">
        <div class="hero-copy reveal">
            <span class="hero-eyebrow">Feito para academias de todos os tamanhos</span>
            <h1>Sua academia <span class="text-gradient">organizada</span>, do check-in à evolução do aluno</h1>
            <p class="lead">Check-in por QR Code, ficha de treino, avaliação física e alunos num só painel. Comece grátis por <?= (int) $trialDias ?> dias, sem cartão de crédito.</p>
            <div class="hero-cta">
                <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-grad">Testar grátis por <?= (int) $trialDias ?> dias</a>
                <a href="#planos" class="btn-k btn-k-outline">Ver planos</a>
            </div>
            <div class="hero-meta">
                <div><strong data-counter="8">0</strong> módulos integrados</div>
                <div><strong data-counter="100">0</strong><span class="unit">%</span> dos seus dados, sua base</div>
                <div><strong>Um QR</strong> resolve entrada e saída</div>
            </div>
        </div>

        <div class="hero-panel-wrapper reveal">
            <div class="hero-panel glass-card">
                <div class="hero-panel-header">
                    <span><i class="bi bi-activity"></i> hoje na academia</span>
                    <div class="hero-panel-dots"><span></span><span></span><span></span></div>
                </div>
                <div class="hero-panel-body">
                    <div class="hero-row">
                        <span><i class="bi bi-qr-code-scan"></i> Check-ins hoje</span>
                        <span class="tag glow">47 alunos</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-people"></i> Na academia agora</span>
                        <span class="tag">12 treinando</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-fire"></i> Maior streak do mês</span>
                        <span class="tag ai">21 dias</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-clipboard2-pulse"></i> Fichas de treino ativas</span>
                        <span class="tag">38</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-qr-code"></i> Pix na recepção</span>
                        <span class="tag glow">recebido</span>
                    </div>
                </div>
            </div>
            <div class="floating-badge badge-1"><i class="bi bi-qr-code-scan"></i> Check-in em tempo real</div>
            <div class="floating-badge badge-2"><i class="bi bi-graph-up-arrow"></i> Evolução do aluno</div>
        </div>
    </div>
</section>

<section class="site-section reveal" id="sobre">
    <div class="site-section-header">
        <h2>Por que usar o KADOSYS</h2>
        <p>Pensado pro dia a dia real de uma academia - não só pra bonito.</p>
    </div>
    <div class="sobre-grid">
        <?php foreach ($sobre as $item): ?>
            <div class="sobre-item">
                <div class="icone"><i class="bi <?= $item['icone'] ?>"></i></div>
                <h3><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($item['texto'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="site-section reveal" id="recursos">
    <div class="site-section-header">
        <h2>O que você vai encontrar</h2>
        <p>Tudo pensado pro dia a dia de uma academia, sem complicação.</p>
    </div>
    <div class="recursos-grid">
        <?php foreach ($recursos as $recurso): ?>
            <div class="glass-card recurso-card">
                <div class="icone"><i class="bi <?= $recurso['icone'] ?>"></i></div>
                <h3><?= htmlspecialchars($recurso['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($recurso['texto'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="site-section reveal" id="funcionalidades">
    <div class="site-section-header">
        <h2>Funcionalidades que fazem diferença</h2>
        <p>Recursos além do básico, incluídos conforme o plano.</p>
    </div>
    <div class="funcionalidades-grid">
        <?php foreach ($funcionalidades as $item): ?>
            <div class="funcionalidade-card">
                <div class="icone"><i class="bi <?= $item['icone'] ?>"></i></div>
                <div>
                    <h3><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($item['texto'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="site-section reveal" id="beneficios">
    <div class="beneficios-card glass-card">
        <div class="site-section-header" style="margin-bottom: 1.5rem;">
            <h2>Sem letra miúda</h2>
        </div>
        <ul class="beneficios-list">
            <?php foreach ($beneficios as $beneficio): ?>
                <li><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($beneficio, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="site-section reveal" id="planos">
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
                    <?php foreach (Plano::features($plano) as $feature): ?>
                        <li><i class="bi bi-check-lg"></i> <?= htmlspecialchars($feature, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= $basePath ?>/cadastro?plano=<?= urlencode($plano) ?>" class="btn-k btn-k-grad">Começar agora</a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="site-section reveal" id="faq">
    <div class="site-section-header">
        <h2>Perguntas frequentes</h2>
        <p>Não achou a sua? Chama no WhatsApp que a gente responde.</p>
    </div>
    <div class="faq-list">
        <?php foreach ($faqs as $faq): ?>
            <div class="faq-item glass-card" data-faq-item>
                <button type="button" class="faq-question">
                    <?= htmlspecialchars($faq['pergunta'], ENT_QUOTES, 'UTF-8') ?>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    <p><?= htmlspecialchars($faq['resposta'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="cta-final reveal">
    <h2>Pronto pra organizar sua academia?</h2>
    <p>Comece grátis hoje - sem cartão, sem compromisso.</p>
    <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-grad">Testar grátis por <?= (int) $trialDias ?> dias</a>
</div>
