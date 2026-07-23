<?php

use Food\Models\Plano;

/**
 * @var array $config
 * @var array<int, string> $planos
 * @var int $trialDias
 */
$basePath = $config['base_path'] ?? '';

$recursos = [
    ['icone' => 'bi-clipboard2-data', 'titulo' => 'Ficha Técnica', 'texto' => 'Custo de cada produto calculado automaticamente a partir dos ingredientes - reajusta sozinho quando o preço de compra muda.'],
    ['icone' => 'bi-boxes', 'titulo' => 'Estoque', 'texto' => 'Ingredientes com estoque baixo, alerta de vencimento e baixa automática a cada venda.'],
    ['icone' => 'bi-cash-coin', 'titulo' => 'Caixa (PDV)', 'texto' => 'Tela de venda rápida com Pix, cartão, dinheiro e múltiplas formas de pagamento na mesma venda.'],
    ['icone' => 'bi-receipt-cutoff', 'titulo' => 'Pedidos', 'texto' => 'Balcão, WhatsApp e delivery próprio num só lugar, com status em tempo real pra cozinha.'],
];

$sobre = [
    ['icone' => 'bi-calculator', 'titulo' => 'Preço certo, sempre', 'texto' => 'Chega de vender no chute: o sistema mostra o custo real e o preço ideal de cada produto, por canal de venda.'],
    ['icone' => 'bi-columns-gap', 'titulo' => 'Tudo em um painel', 'texto' => 'Ficha técnica, estoque, PDV, pedidos e financeiro num só lugar - sem planilha, sem caderno.'],
    ['icone' => 'bi-graph-up-arrow', 'titulo' => 'Lucro visível', 'texto' => 'Saiba exatamente quanto sobra líquido depois de comissão do iFood, taxas e despesas.'],
];

$funcionalidades = [
    ['icone' => 'bi-egg-fried', 'titulo' => 'Ficha técnica automática', 'texto' => 'Cadastre os ingredientes de cada receita e o sistema calcula custo, markup e preço ideal por canal (balcão, WhatsApp, iFood, delivery).'],
    ['icone' => 'bi-percent', 'titulo' => 'Taxa do iFood embutida', 'texto' => 'Comissão de 12% + taxa fixa por distância (Entrega II) já calculadas na hora de precificar - você vê o valor líquido antes de vender.'],
    ['icone' => 'bi-qr-code', 'titulo' => 'Pix direto no PDV', 'texto' => 'Cadastre sua chave Pix e gere QR Code com o valor exato de cada venda, sem taxa de gateway.'],
    ['icone' => 'bi-truck', 'titulo' => 'Produção em tempo real', 'texto' => 'Tela tipo cozinha (ou pra TV) com timer automático e som a cada pedido novo, do recebido ao entregue.'],
    ['icone' => 'bi-basket3', 'titulo' => 'Compras e fornecedores', 'texto' => 'Registre a compra e o estoque entra sozinho - o custo do ingrediente atualiza a ficha técnica na hora.'],
    ['icone' => 'bi-wallet2', 'titulo' => 'Financeiro completo', 'texto' => 'Contas a pagar e receber, fluxo de caixa, DRE e lucro diário/mensal, sempre atualizados.'],
    ['icone' => 'bi-people', 'titulo' => 'Clientes', 'texto' => 'Ticket médio, frequência e histórico de cada cliente, sempre à mão na hora de vender.'],
    ['icone' => 'bi-palette', 'titulo' => 'Sua marca', 'texto' => 'Logo e cor de destaque personalizadas em todo o painel.'],
];

$beneficios = [
    'Comece a usar em minutos, sem instalar nada',
    'Seus dados, sempre disponíveis, com backup automático',
    'Suporte por WhatsApp direto com quem construiu o sistema',
    'Sem contrato de fidelidade - cancele quando quiser',
];

$faqs = [
    ['pergunta' => 'Preciso de cartão de crédito pra testar?', 'resposta' => 'Não. O trial de ' . (int) $trialDias . ' dias começa sem cartão nem qualquer cobrança - você só assina se quiser continuar depois.'],
    ['pergunta' => 'Como funciona o cálculo da ficha técnica?', 'resposta' => 'Você cadastra os ingredientes de cada produto (quantidade, unidade, perda) e informa os custos de energia, gás, água, embalagem e mão de obra - o sistema soma tudo e sugere o preço ideal por canal de venda.'],
    ['pergunta' => 'A taxa do iFood é calculada automaticamente?', 'resposta' => 'Sim, no plano Entrega II: 12% de comissão sobre o valor do pedido mais a taxa fixa por distância (até 3km R$3,99, 3-5km R$5,99, 5-7km R$7,99, acima de 7km R$9,99) - o sistema mostra exatamente quanto sobra líquido por venda.'],
    ['pergunta' => 'Como funciona o pagamento do plano?', 'resposta' => 'Você escolhe entre Pix (chave copia-e-cola, sem taxa) ou cartão de crédito recorrente via Mercado Pago. Dá pra trocar de plano ou forma de pagamento a qualquer momento na dashboard.'],
    ['pergunta' => 'Posso cancelar quando quiser?', 'resposta' => 'Sim, não tem fidelidade. Você cancela direto pela dashboard e continua usando até o fim do período já pago.'],
    ['pergunta' => 'Já integra direto com o iFood?', 'resposta' => 'Por enquanto os pedidos do iFood são lançados manualmente no painel (pra controle de estoque e financeiro) - a integração automática por API está no roadmap.'],
];

?>
<section class="hero">
    <div class="hero-inner">
        <div class="hero-copy reveal">
            <span class="hero-eyebrow">Feito para confeitarias, restaurantes e delivery</span>
            <h1>Seu preço <span class="text-gradient">certo</span>, do ingrediente ao pedido entregue</h1>
            <p class="lead">Ficha técnica, estoque, PDV, pedidos e financeiro num só painel. Comece grátis por <?= (int) $trialDias ?> dias, sem cartão de crédito.</p>
            <div class="hero-cta">
                <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-grad">Testar grátis por <?= (int) $trialDias ?> dias</a>
                <a href="#planos" class="btn-k btn-k-outline">Ver planos</a>
            </div>
            <div class="hero-meta">
                <div><strong data-counter="8">0</strong> módulos integrados</div>
                <div><strong data-counter="100">0</strong><span class="unit">%</span> dos seus dados, sua base</div>
                <div><strong>Custo real</strong>, calculado automaticamente</div>
            </div>
        </div>

        <div class="hero-panel-wrapper reveal">
            <div class="hero-panel glass-card">
                <div class="hero-panel-header">
                    <span><i class="bi bi-activity"></i> hoje na loja</span>
                    <div class="hero-panel-dots"><span></span><span></span><span></span></div>
                </div>
                <div class="hero-panel-body">
                    <div class="hero-row">
                        <span><i class="bi bi-clipboard2-data"></i> Ficha técnica recalculada</span>
                        <span class="tag">automática</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-receipt-cutoff"></i> Pedidos em preparo agora</span>
                        <span class="tag glow">4 na cozinha</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-percent"></i> Taxa iFood calculada</span>
                        <span class="tag">líquido visível</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-qr-code"></i> Pix no PDV</span>
                        <span class="tag glow">recebido</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-boxes"></i> Estoque baixo</span>
                        <span class="tag ai">2 ingredientes</span>
                    </div>
                </div>
            </div>
            <div class="floating-badge badge-1"><i class="bi bi-broadcast"></i> Produção em tempo real</div>
            <div class="floating-badge badge-2"><i class="bi bi-graph-up-arrow"></i> Lucro sempre visível</div>
        </div>
    </div>
</section>

<section class="site-section reveal" id="sobre">
    <div class="site-section-header">
        <h2>Por que usar o KADOSYS</h2>
        <p>Pensado pro dia a dia real de uma cozinha - não só pra bonito.</p>
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
        <p>Tudo pensado pro dia a dia de uma confeitaria, restaurante ou delivery, sem complicação.</p>
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
    <h2>Pronto pra saber o preço certo de cada produto?</h2>
    <p>Comece grátis hoje - sem cartão, sem compromisso.</p>
    <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-grad">Testar grátis por <?= (int) $trialDias ?> dias</a>
</div>
