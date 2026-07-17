<?php

use Barbearias\Models\Plano;

/**
 * @var array $config
 * @var array<int, string> $planos
 * @var int $trialDias
 */
$basePath = $config['base_path'] ?? '';

$recursos = [
    ['icone' => 'bi-calendar2-week', 'titulo' => 'Agendamentos', 'texto' => 'Agenda por profissional, com lembrete automático pro cliente antes do horário.'],
    ['icone' => 'bi-person-badge', 'titulo' => 'Profissionais', 'texto' => 'Cadastre a equipe, defina horários e acompanhe a produtividade de cada um.'],
    ['icone' => 'bi-scissors', 'titulo' => 'Serviços', 'texto' => 'Corte, barba, combos - com duração e preço configuráveis.'],
    ['icone' => 'bi-people', 'titulo' => 'Clientes', 'texto' => 'Histórico completo de cada cliente, sempre à mão na hora de atender.'],
];

$sobre = [
    ['icone' => 'bi-bell', 'titulo' => 'Menos faltas', 'texto' => 'Lembrete automático antes do horário reduz o cliente que esquece e não aparece.'],
    ['icone' => 'bi-columns-gap', 'titulo' => 'Tudo em um painel', 'texto' => 'Agenda, financeiro, comissão e clientes num só lugar - sem planilha, sem caderno.'],
    ['icone' => 'bi-shop', 'titulo' => 'Cresce com você', 'texto' => 'De uma cadeira a várias unidades, o sistema acompanha o tamanho da sua operação.'],
];

$funcionalidades = [
    ['icone' => 'bi-people-fill', 'titulo' => 'Fila de atendimento', 'texto' => 'Não trabalha com horário marcado? Ative o modo fila: cliente entra pelo link, você chama por ordem de chegada.'],
    ['icone' => 'bi-qr-code', 'titulo' => 'Pix direto no PDV', 'texto' => 'Cadastre sua chave Pix e gere QR Code na hora de fechar o atendimento, sem taxa de gateway.'],
    ['icone' => 'bi-cash-stack', 'titulo' => 'Comissão automática', 'texto' => 'Calcula a comissão de cada profissional pelos atendimentos concluídos e paga direto do caixa.'],
    ['icone' => 'bi-star', 'titulo' => 'Fidelidade', 'texto' => 'Pontos por atendimento pago, trocados por recompensas que você define.'],
    ['icone' => 'bi-arrow-repeat', 'titulo' => 'Assinaturas de cliente', 'texto' => 'Pacotes pré-pagos de atendimentos por mês, com controle de uso automático.'],
    ['icone' => 'bi-graph-up', 'titulo' => 'Relatórios', 'texto' => 'Faturamento, ticket médio e ocupação da agenda, sempre atualizados.'],
    ['icone' => 'bi-tv', 'titulo' => 'Painel de recepção', 'texto' => 'Tela pensada pra ficar numa TV do salão, mostrando a fila do dia em tempo real.'],
    ['icone' => 'bi-palette', 'titulo' => 'Sua marca', 'texto' => 'Logo e cor de destaque personalizadas na página pública de agendamento.'],
];

$beneficios = [
    'Comece a usar em minutos, sem instalar nada',
    'Seus dados, sempre disponíveis, com backup automático',
    'Suporte por WhatsApp direto com quem construiu o sistema',
    'Sem contrato de fidelidade - cancele quando quiser',
];

$faqs = [
    ['pergunta' => 'Preciso de cartão de crédito pra testar?', 'resposta' => 'Não. O trial de ' . (int) $trialDias . ' dias começa sem cartão nem qualquer cobrança - você só assina se quiser continuar depois.'],
    ['pergunta' => 'Dá pra usar sem agendamento marcado?', 'resposta' => 'Sim. Em Configurações você pode trocar pro modo Fila: o cliente entra numa fila de espera (pelo link público ou pela equipe) e é chamado por ordem de chegada, sem hora marcada.'],
    ['pergunta' => 'Como funciona o pagamento do plano?', 'resposta' => 'Você escolhe entre Pix (chave copia-e-cola, sem taxa) ou cartão de crédito recorrente via Mercado Pago. Dá pra trocar de plano ou forma de pagamento a qualquer momento na dashboard.'],
    ['pergunta' => 'Posso cancelar quando quiser?', 'resposta' => 'Sim, não tem fidelidade. Você cancela direto pela dashboard e continua usando até o fim do período já pago.'],
    ['pergunta' => 'O sistema funciona pra mais de uma unidade?', 'resposta' => 'Sim. A partir do plano Enterprise você cadastra quantas unidades quiser, cada uma com seus profissionais e horários.'],
    ['pergunta' => 'Meus clientes precisam baixar algum aplicativo?', 'resposta' => 'Não. O agendamento é feito por um link web que você compartilha no Instagram ou WhatsApp - funciona direto no navegador do celular.'],
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
    <div class="hero-inner">
        <div class="hero-copy reveal">
            <span class="hero-eyebrow">Feito para barbearias de todos os tamanhos</span>
            <h1>Sua barbearia <span class="text-gradient">organizada</span>, do agendamento ao cliente fidelizado</h1>
            <p class="lead">Agenda, profissionais, serviços e clientes num só painel. Comece grátis por <?= (int) $trialDias ?> dias, sem cartão de crédito.</p>
            <div class="hero-cta">
                <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-grad">Testar grátis por <?= (int) $trialDias ?> dias</a>
                <a href="#planos" class="btn-k btn-k-outline">Ver planos</a>
            </div>
            <div class="hero-meta">
                <div><strong data-counter="8">0</strong> módulos integrados</div>
                <div><strong data-counter="100">0</strong><span class="unit">%</span> dos seus dados, sua base</div>
                <div><strong>Fila</strong> ou agendamento, você escolhe</div>
            </div>
        </div>

        <div class="hero-panel-wrapper reveal">
            <div class="hero-panel glass-card">
                <div class="hero-panel-header">
                    <span><i class="bi bi-activity"></i> hoje na barbearia</span>
                    <div class="hero-panel-dots"><span></span><span></span><span></span></div>
                </div>
                <div class="hero-panel-body">
                    <div class="hero-row">
                        <span><i class="bi bi-calendar2-check"></i> Próximo horário &middot; 14h30</span>
                        <span class="tag">confirmado</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-people"></i> Fila de espera agora</span>
                        <span class="tag glow">3 aguardando</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-cash-coin"></i> Comissão do mês</span>
                        <span class="tag">calculada</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-qr-code"></i> Pix no PDV</span>
                        <span class="tag glow">recebido</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-star"></i> Pontos de fidelidade</span>
                        <span class="tag ai">+12 hoje</span>
                    </div>
                </div>
            </div>
            <div class="floating-badge badge-1"><i class="bi bi-broadcast"></i> Fila em tempo real</div>
            <div class="floating-badge badge-2"><i class="bi bi-graph-up-arrow"></i> Equipe organizada</div>
        </div>
    </div>
</section>

<section class="site-section reveal" id="sobre">
    <div class="site-section-header">
        <h2>Por que usar o KADOSYS</h2>
        <p>Pensado pro dia a dia real de uma barbearia - não só pra bonito.</p>
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
        <p>Tudo pensado pro dia a dia de uma barbearia, sem complicação.</p>
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
                    <?php foreach ($planosFeatures[$plano] as $feature): ?>
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
    <h2>Pronto pra organizar sua barbearia?</h2>
    <p>Comece grátis hoje - sem cartão, sem compromisso.</p>
    <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-grad">Testar grátis por <?= (int) $trialDias ?> dias</a>
</div>
