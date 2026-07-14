<?php

use Igrejas\Controllers\DashboardController;
use Igrejas\Models\Plano;

/**
 * @var array $config
 */
$basePath = $config['base_path'] ?? '';

// Tabela de comparacao de planos: gerada a partir do mesmo mapa modulo -> plano
// usado de verdade pro controle de acesso (ver Igrejas\Models\Plano e
// PlanoMiddleware), pra pagina de vendas nunca ficar desatualizada em
// relacao ao que o sistema realmente libera em cada plano.
$planosComparacao = [Plano::ESSENCIAL, Plano::PREMIUM, Plano::ENTERPRISE];
// "configuracoes" e "usuarios" ficam fora da tabela: nao sao recursos de
// venda, e sim ajustes basicos do sistema disponiveis em qualquer plano.
$modulosComparacao = array_filter(
    DashboardController::modules(),
    static fn (string $slug) => !in_array($slug, ['configuracoes', 'usuarios'], true),
    ARRAY_FILTER_USE_KEY
);
?>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div class="hero-copy reveal">
            <span class="eyebrow">Kadosys Igrejas &middot; Gestão inteligente</span>
            <h1>A gestão da sua igreja, <span class="text-gradient">inteligente e conectada</span>.</h1>
            <p class="lead">
                Membros, ministérios, cultos, agenda, financeiro e patrimônio em uma única
                plataforma moderna, com automação e sincronização em tempo real a serviço da
                rotina real de quem administra a igreja.
            </p>
            <div class="hero-actions">
                <a href="#planos" class="btn-k btn-k-grad">Começar agora</a>
                <a href="<?= $basePath ?>/login" class="btn-k btn-k-outline">
                    Acessar o sistema <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="hero-meta">
                <div><strong data-counter="14">0</strong> módulos integrados</div>
                <div><strong data-counter="100">0</strong><span class="unit">%</span> dados na sua própria base</div>
                <div><strong>Ao vivo</strong> cifra, tom e telão sincronizados</div>
            </div>
        </div>

        <div class="hero-panel-wrapper reveal">
            <div class="hero-panel glass-card">
                <div class="hero-panel-header">
                    <span><i class="bi bi-activity"></i> painel em tempo real</span>
                    <div class="hero-panel-dots"><span></span><span></span><span></span></div>
                </div>
                <div class="hero-panel-body">
                    <div class="hero-row">
                        <span><i class="bi bi-calendar2-week"></i> Culto de domingo &middot; 10h</span>
                        <span class="tag">agendado</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-people"></i> Novos membros este mês</span>
                        <span class="tag glow">+18</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-music-note-beamed"></i> Ministério de louvor</span>
                        <span class="tag">14 voluntários</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-cash-coin"></i> Dízimos e ofertas</span>
                        <span class="tag glow">atualizado</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-music-note-list"></i> Modo Culto</span>
                        <span class="tag ai">tom: G &rarr; A, ao vivo</span>
                    </div>
                </div>
            </div>
            <div class="floating-badge badge-1"><i class="bi bi-broadcast"></i> Ao vivo</div>
            <div class="floating-badge badge-2"><i class="bi bi-graph-up-arrow"></i> +85% Organização</div>
        </div>
    </div>
</section>

<!-- SOBRE -->
<section class="landing-section" id="sobre">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Sobre o sistema</span>
            <h2 class="section-title">Feito para a rotina da igreja, <span class="text-gradient">potencializado por tecnologia</span></h2>
            <p class="section-lead">
                O KADOSYS Igrejas reúne, em um único lugar, os processos que normalmente se perdem entre
                planilhas, papéis e grupos de mensagens: cadastro de membros, organização de ministérios,
                controle financeiro e comunicação com a congregação.
            </p>
        </div>

        <div class="sobre-grid">
            <div class="sobre-item glass-card reveal">
                <div class="icon"><i class="bi bi-hdd-network"></i></div>
                <h3>Centralizado</h3>
                <p>Toda a informação da igreja em uma única plataforma na nuvem, acessível de qualquer lugar.</p>
            </div>
            <div class="sobre-item glass-card reveal">
                <div class="icon"><i class="bi bi-shield-check"></i></div>
                <h3>Seguro</h3>
                <p>Cada igreja com seu próprio banco de dados, autenticação protegida e controle de acesso.</p>
            </div>
            <div class="sobre-item glass-card reveal">
                <div class="icon"><i class="bi bi-broadcast"></i></div>
                <h3>Em tempo real</h3>
                <p>Modo Culto, telão e preletor sincronizados ao vivo entre os aparelhos - sem recarregar página.</p>
            </div>
        </div>
    </div>
</section>

<!-- RECURSOS -->
<section class="landing-section alt" id="recursos">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Recursos</span>
            <h2 class="section-title">Tudo que a secretaria, a tesouraria e a <span class="text-gradient">liderança precisam</span></h2>
            <p class="section-lead">
                Recursos pensados para reduzir o trabalho manual e dar visibilidade sobre o que importa.
            </p>
        </div>

        <div class="cards-grid">
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-people"></i></div>
                <h3>Gestão de membros</h3>
                <p>Cadastro completo, histórico e acompanhamento de cada membro da congregação.</p>
                <a href="<?= $basePath ?>/recursos/membros" class="feature-card-link">Saiba mais <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-diagram-3"></i></div>
                <h3>Ministérios e grupos</h3>
                <p>Organização de ministérios, pequenos grupos e suas respectivas lideranças.</p>
                <a href="<?= $basePath ?>/recursos/ministerios" class="feature-card-link">Saiba mais <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-cash-coin"></i></div>
                <h3>Financeiro</h3>
                <p>Controle de dízimos, ofertas e despesas com relatórios claros.</p>
                <a href="<?= $basePath ?>/recursos/financeiro" class="feature-card-link">Saiba mais <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-calendar3"></i></div>
                <h3>Agenda e cultos</h3>
                <p>Programação de cultos, eventos e compromissos da igreja em um calendário único.</p>
                <a href="<?= $basePath ?>/recursos/agenda" class="feature-card-link">Saiba mais <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-building"></i></div>
                <h3>Patrimônio</h3>
                <p>Controle de bens, imóveis e equipamentos sob responsabilidade da igreja.</p>
                <a href="<?= $basePath ?>/recursos/patrimonio" class="feature-card-link">Saiba mais <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-megaphone"></i></div>
                <h3>Comunicação</h3>
                <p>Avisos e comunicados centralizados para membros e liderança.</p>
                <a href="<?= $basePath ?>/recursos/comunicacao" class="feature-card-link">Saiba mais <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-easel2"></i></div>
                <h3>Projeção e Telão</h3>
                <p>Bíblia, vídeos e letras projetados ao vivo no telão, com controle remoto para o preletor direto do celular ou tablet durante o culto.</p>
                <a href="<?= $basePath ?>/recursos/projecao" class="feature-card-link">Saiba mais <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-music-note-list"></i></div>
                <h3>Louvores e Modo Culto</h3>
                <p>Cifra e tom sincronizados ao vivo com todo o time - o líder muda o tom no celular e a cifra se ajusta sozinha pra todo mundo, na hora.</p>
                <a href="<?= $basePath ?>/recursos/louvores" class="feature-card-link">Saiba mais <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-music-note-beamed"></i></div>
                <h3>Playbacks</h3>
                <p>Biblioteca de playbacks para o ministério de louvor, liberada em todos os planos - sem custo extra.</p>
                <a href="<?= $basePath ?>/recursos/playbacks" class="feature-card-link">Saiba mais <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- FUNCIONALIDADES -->
<section class="landing-section" id="funcionalidades">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Funcionalidades</span>
            <h2 class="section-title">Um painel administrativo <span class="text-gradient">de nova geração</span></h2>
            <p class="section-lead">
                Dashboard moderno, com visão clara dos indicadores, navegação simples e
                controle detalhado de quem acessa o quê.
            </p>
        </div>

        <div class="cards-grid">
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-speedometer2"></i></div>
                <h3>Dashboard com indicadores</h3>
                <p>Visão geral da igreja com números atualizados dos principais módulos, direto na tela inicial.</p>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-person-plus"></i></div>
                <h3>Autocadastro de membros</h3>
                <p>Link público opcional onde o próprio membro se cadastra, com endereço preenchido automaticamente pelo CEP.</p>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-shield-lock"></i></div>
                <h3>Usuários e permissões</h3>
                <p>Convide sua equipe com login próprio - escolha, módulo a módulo, quem só visualiza e quem pode editar.</p>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-bar-chart-line"></i></div>
                <h3>Relatórios</h3>
                <p>Indicadores consolidados de frequência, membros e finanças, sem precisar montar planilha.</p>
            </div>
        </div>
    </div>
</section>

<!-- BENEFICIOS -->
<section class="landing-section alt" id="beneficios">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Benefícios</span>
            <h2 class="section-title">Por que igrejas escolhem o <span class="text-gradient">KADOSYS</span></h2>
        </div>

        <div class="beneficios-list">
            <div class="beneficio-row glass-card reveal">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Menos planilhas, mais clareza</h4>
                    <p>Membros, financeiro, agenda e patrimônio saem das planilhas soltas e do papel para um sistema único, organizado por módulo.</p>
                </div>
            </div>
            <div class="beneficio-row glass-card reveal">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Dados exclusivos da sua igreja</h4>
                    <p>Cada instalação utiliza seu próprio banco de dados, sem compartilhamento com outras igrejas.</p>
                </div>
            </div>
            <div class="beneficio-row glass-card reveal">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Pix direto pra conta da igreja</h4>
                    <p>Doações via Pix caem direto na conta da própria igreja - sem intermediário, sem taxa da plataforma em cima do valor.</p>
                </div>
            </div>
            <div class="beneficio-row glass-card reveal">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Evolui continuamente</h4>
                    <p>Novos módulos e melhorias são lançados com frequência, sem custo extra pra igreja e sem precisar migrar nada.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CAPTURAS DE TELA -->
<section class="landing-section" id="capturas">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Capturas de tela</span>
            <h2 class="section-title">Conheça a interface do <span class="text-gradient">sistema</span></h2>
            <p class="section-lead">Telas reais do KADOSYS Igrejas rodando - cada módulo tem uma página própria, com mais detalhes.</p>
        </div>

        <div class="screens-grid">
            <a href="<?= $basePath ?>/recursos/louvores" class="screen-thumb glass-card reveal">
                <img src="<?= $basePath ?>/assets/img/recursos/louvores.png" alt="Modo Culto com cifra e tom ao vivo" loading="lazy">
                <span class="screen-thumb-label"><i class="bi bi-music-note-list"></i> Louvores &middot; Modo Culto</span>
            </a>
            <a href="<?= $basePath ?>/recursos/projecao" class="screen-thumb glass-card reveal">
                <img src="<?= $basePath ?>/assets/img/recursos/projecao.png" alt="Telão exibindo versículo bíblico" loading="lazy">
                <span class="screen-thumb-label"><i class="bi bi-easel2"></i> Projeção &middot; Telão</span>
            </a>
            <a href="<?= $basePath ?>/recursos/agenda" class="screen-thumb glass-card reveal">
                <img src="<?= $basePath ?>/assets/img/recursos/agenda.png" alt="Calendário da Agenda com cultos e eventos" loading="lazy">
                <span class="screen-thumb-label"><i class="bi bi-calendar3"></i> Agenda</span>
            </a>
        </div>

        <div class="section-footer-link reveal">
            <a href="<?= $basePath ?>/recursos/membros">Ver todos os recursos em detalhe <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</section>

<!-- PLANOS -->
<section class="landing-section alt" id="planos">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Planos</span>
            <h2 class="section-title">Escolha o plano ideal para a <span class="text-gradient">sua igreja</span></h2>
            <p class="section-lead">Planos pensados para igrejas de diferentes tamanhos e necessidades.</p>
        </div>

        <div class="plan-trial-banner glass-card reveal">
            <div class="plan-trial-banner-icon"><i class="bi bi-gift"></i></div>
            <div class="plan-trial-banner-copy">
                <h3>Teste grátis por 7 dias</h3>
                <p>Experimente o sistema completo, sem cartão de crédito e sem compromisso. Depois escolha o plano que combina com a sua igreja.</p>
            </div>
            <a href="<?= $basePath ?>/cadastro?metodo_pagamento=trial" class="btn-k btn-k-grad">
                Testar grátis agora <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="plans-grid">
            <div class="plan-card glass-card reveal">
                <div class="plan-icon"><i class="bi bi-rocket-takeoff"></i></div>
                <h3>Essencial</h3>
                <p class="plan-desc">Para igrejas pequenas que estão começando a organizar sua gestão.</p>
                <div class="plan-price">R$ 49,90<span>/mês</span></div>
                <ul class="plan-features">
                    <li class="plan-feature"><i class="bi bi-check2"></i> Membros e Equipe</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Cultos e Agenda, com recorrência automática</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Projeção/Telão com controle do preletor em tempo real</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Louvores e Modo Culto, com transposição de tom ao vivo</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Playbacks (biblioteca de áudio)</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Backup automático e suporte por e-mail</li>
                </ul>
                <a href="<?= $basePath ?>/cadastro?plano=essencial" class="btn-k btn-k-outline">Começar agora</a>
            </div>

            <div class="plan-card glass-card featured reveal">
                <span class="badge-featured">Mais escolhido</span>
                <div class="plan-icon"><i class="bi bi-stars"></i></div>
                <h3>Plus</h3>
                <p class="plan-desc">Para igrejas em crescimento, com ministérios e finanças ativas.</p>
                <div class="plan-price">R$ 99,90<span>/mês</span></div>
                <ul class="plan-features">
                    <li class="plan-feature"><i class="bi bi-check2"></i> Tudo do plano Essencial</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Ministérios (líderes e voluntários)</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Grupos, células e classes</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Financeiro, com doação via Pix direto pra conta da igreja</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Comunicação (avisos pra todos ou só liderança)</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Suporte prioritário</li>
                </ul>
                <a href="<?= $basePath ?>/cadastro?plano=premium" class="btn-k btn-k-grad">Começar agora</a>
            </div>

            <div class="plan-card glass-card reveal">
                <div class="plan-icon"><i class="bi bi-gem"></i></div>
                <h3>Premium</h3>
                <p class="plan-desc">Para igrejas médias e grandes que querem controle e prioridade.</p>
                <div class="plan-price">R$ 179,90<span>/mês</span></div>
                <ul class="plan-features">
                    <li class="plan-feature"><i class="bi bi-check2"></i> Tudo do plano Plus</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Patrimônio (bens, imóveis e equipamentos)</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Relatórios consolidados</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Permissões por usuário: escolha quem só visualiza e quem edita cada módulo</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Atendimento dedicado</li>
                </ul>
                <a href="<?= $basePath ?>/cadastro?plano=enterprise" class="btn-k btn-k-outline">Começar agora</a>
            </div>
        </div>

        <div class="plan-compare-toggle-wrap reveal">
            <button type="button" class="plan-compare-toggle" data-plan-compare-toggle aria-expanded="false">
                <i class="bi bi-table"></i> <span data-plan-compare-toggle-label>Comparar todos os recursos</span>
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>

        <div class="plan-compare-wrap" data-plan-compare hidden>
            <div class="plan-compare-scroll">
                <table class="plan-compare-table">
                    <thead>
                        <tr>
                            <th class="plan-compare-feature-col">Recurso</th>
                            <?php foreach ($planosComparacao as $plano): ?>
                                <th><?= htmlspecialchars(Plano::label($plano), ENT_QUOTES, 'UTF-8') ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modulosComparacao as $slug => $modulo): ?>
                            <tr>
                                <td class="plan-compare-feature-col">
                                    <i class="bi <?= htmlspecialchars($modulo['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                    <?= htmlspecialchars($modulo['title'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <?php foreach ($planosComparacao as $plano): ?>
                                    <td>
                                        <?php if (Plano::disponivel($plano, $slug)): ?>
                                            <i class="bi bi-check-circle-fill plan-compare-yes"></i>
                                        <?php else: ?>
                                            <i class="bi bi-dash-lg plan-compare-no"></i>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td class="plan-compare-feature-col"><i class="bi bi-headset"></i> Suporte</td>
                            <td>E-mail</td>
                            <td>E-mail prioritário</td>
                            <td>Dedicado</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="landing-section" id="faq">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Perguntas frequentes</span>
            <h2 class="section-title">Ainda com <span class="text-gradient">dúvidas?</span></h2>
        </div>

        <div class="faq-list">
            <div class="faq-item glass-card reveal" data-faq-item>
                <button class="faq-question">
                    Os dados da minha igreja são compartilhados com outras igrejas?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>Não. Cada instalação do KADOSYS Igrejas utiliza seu próprio banco de dados,
                        exclusivo para a sua igreja, sem compartilhamento com outras instalações.</p>
                </div>
            </div>

            <div class="faq-item glass-card reveal" data-faq-item>
                <button class="faq-question">
                    Posso mudar de plano depois?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>Sim. É possível alterar o plano contratado conforme a igreja cresce e novas
                        necessidades surgem.</p>
                </div>
            </div>

            <div class="faq-item glass-card reveal" data-faq-item>
                <button class="faq-question">
                    O sistema funciona em celular?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>Sim. A interface é responsiva e se adapta a celulares, tablets e computadores.</p>
                </div>
            </div>

            <div class="faq-item glass-card reveal" data-faq-item>
                <button class="faq-question">
                    O que é o Modo Culto e como funciona a troca de tom ao vivo?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>É o modo de tela do módulo Louvores feito pra usar durante o culto: o time de
                        louvor acompanha a cifra da música em qualquer celular ou tablet, e quando o
                        líder muda o tom, a cifra de todo mundo se ajusta sozinha, na hora, sem
                        precisar recarregar a página. Disponível em todos os planos.</p>
                </div>
            </div>

            <div class="faq-item glass-card reveal" data-faq-item>
                <button class="faq-question">
                    O que são os Playbacks?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>É a biblioteca de faixas de áudio do ministério de louvor: upload, organização
                        e player pra tocar as bases direto do sistema, sem depender de pendrive ou
                        app externo. Disponível em todos os planos.</p>
                </div>
            </div>

            <div class="faq-item glass-card reveal" data-faq-item>
                <button class="faq-question">
                    Como funcionam os níveis de permissão por usuário?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>No plano Premium, cada usuário da equipe pode receber acesso configurado
                        módulo a módulo: sem acesso, só visualizar ou visualizar e editar. Assim dá
                        pra convidar voluntários e líderes com o acesso certo pra função de cada um,
                        sem abrir mão de controle.</p>
                </div>
            </div>

            <div class="faq-item glass-card reveal" data-faq-item>
                <button class="faq-question">
                    Como funcionam as doações via Pix?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>A igreja cadastra sua própria chave Pix, e o sistema gera um link público e um
                        QR Code de doação - inclusive pra exibir direto no telão durante o culto. O
                        valor cai direto na conta da igreja, sem intermediário nem taxa da
                        plataforma em cima da doação.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA FINAL -->
<section class="cta-final">
    <div class="container">
        <div class="cta-final-card glass-card reveal">
            <span class="eyebrow">Comece hoje</span>
            <h2 class="section-title">Leve a gestão da sua igreja para <span class="text-gradient">o futuro</span></h2>
            <p>Acesse o sistema ou escolha um plano para começar.</p>
            <div class="cta-final-actions">
                <a href="#planos" class="btn-k btn-k-grad">Começar agora</a>
                <a href="<?= $basePath ?>/login" class="btn-k btn-k-outline">Acessar o sistema</a>
            </div>
        </div>
    </div>
</section>
