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
// venda (configuracoes e so ajuste do sistema, e usuarios ja tem sua
// propria linha "Usuarios administradores" com a contagem de assentos).
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
            <span class="eyebrow">Kadosys Igrejas &middot; Gestao inteligente</span>
            <h1>A gestao da sua igreja, <span class="text-gradient">inteligente e conectada</span>.</h1>
            <p class="lead">
                Membros, ministerios, cultos, agenda, financeiro e patrimonio em uma unica
                plataforma moderna, com automacao e inteligencia artificial a servico da
                rotina real de quem administra a igreja.
            </p>
            <div class="hero-actions">
                <a href="#planos" class="btn-k btn-k-grad">Comecar agora</a>
                <a href="<?= $basePath ?>/login" class="btn-k btn-k-outline">
                    Acessar o sistema <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="hero-meta">
                <div><strong data-counter="12">0</strong> modulos integrados</div>
                <div><strong data-counter="100">0</strong><span class="unit">%</span> dados na sua propria base</div>
                <div><strong>IA</strong> integrada a plataforma</div>
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
                        <span><i class="bi bi-people"></i> Novos membros este mes</span>
                        <span class="tag glow">+18</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-music-note-beamed"></i> Ministerio de louvor</span>
                        <span class="tag">14 voluntarios</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-cash-coin"></i> Dizimos e ofertas</span>
                        <span class="tag glow">atualizado</span>
                    </div>
                    <div class="hero-row">
                        <span><i class="bi bi-stars"></i> Insight da IA</span>
                        <span class="tag ai">frequencia +12%</span>
                    </div>
                </div>
            </div>
            <div class="floating-badge badge-1"><i class="bi bi-stars"></i> IA Ativa</div>
            <div class="floating-badge badge-2"><i class="bi bi-graph-up-arrow"></i> +85% Organizacao</div>
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
                O KADOSYS Igrejas reune, em um unico lugar, os processos que normalmente se perdem entre
                planilhas, papeis e grupos de mensagens: cadastro de membros, organizacao de ministerios,
                controle financeiro e comunicacao com a congregacao.
            </p>
        </div>

        <div class="sobre-grid">
            <div class="sobre-item glass-card reveal">
                <div class="icon"><i class="bi bi-hdd-network"></i></div>
                <h3>Centralizado</h3>
                <p>Toda a informacao da igreja em uma unica plataforma na nuvem, acessivel de qualquer lugar.</p>
            </div>
            <div class="sobre-item glass-card reveal">
                <div class="icon"><i class="bi bi-shield-check"></i></div>
                <h3>Seguro</h3>
                <p>Cada igreja com seu proprio banco de dados, autenticacao protegida e controle de acesso.</p>
            </div>
            <div class="sobre-item glass-card reveal">
                <div class="icon"><i class="bi bi-cpu"></i></div>
                <h3>Inteligente</h3>
                <p>Estrutura preparada para automacoes e recursos de IA que evoluem junto com a sua gestao.</p>
            </div>
        </div>
    </div>
</section>

<!-- RECURSOS -->
<section class="landing-section alt" id="recursos">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Recursos</span>
            <h2 class="section-title">Tudo que a secretaria, a tesouraria e a <span class="text-gradient">lideranca precisam</span></h2>
            <p class="section-lead">
                Recursos pensados para reduzir o trabalho manual e dar visibilidade sobre o que importa.
            </p>
        </div>

        <div class="cards-grid">
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-people"></i></div>
                <h3>Gestao de membros</h3>
                <p>Cadastro completo, historico e acompanhamento de cada membro da congregacao.</p>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-diagram-3"></i></div>
                <h3>Ministerios e grupos</h3>
                <p>Organizacao de ministerios, pequenos grupos e suas respectivas liderancas.</p>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-cash-coin"></i></div>
                <h3>Financeiro</h3>
                <p>Controle de dizimos, ofertas e despesas com relatorios claros.</p>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-calendar3"></i></div>
                <h3>Agenda e cultos</h3>
                <p>Programacao de cultos, eventos e compromissos da igreja em um calendario unico.</p>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-building"></i></div>
                <h3>Patrimonio</h3>
                <p>Controle de bens, imoveis e equipamentos sob responsabilidade da igreja.</p>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-megaphone"></i></div>
                <h3>Comunicacao</h3>
                <p>Avisos e comunicados centralizados para membros e lideranca.</p>
            </div>
        </div>
    </div>
</section>

<!-- FUNCIONALIDADES -->
<section class="landing-section" id="funcionalidades">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Funcionalidades</span>
            <h2 class="section-title">Um painel administrativo <span class="text-gradient">de nova geracao</span></h2>
            <p class="section-lead">
                Dashboard moderno, com visao clara dos indicadores, navegacao simples e
                insights automaticos entre os modulos.
            </p>
        </div>

        <div class="cards-grid">
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-speedometer2"></i></div>
                <h3>Dashboard com indicadores</h3>
                <p>Visao geral da igreja com numeros atualizados dos principais modulos.</p>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-stars"></i></div>
                <h3>Insights com IA</h3>
                <p>Resumos e alertas inteligentes sobre frequencia, financas e engajamento.</p>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-shield-lock"></i></div>
                <h3>Usuarios e permissoes</h3>
                <p>Controle de quem acessa cada area do sistema, por perfil de usuario.</p>
            </div>
            <div class="feature-card glass-card reveal">
                <div class="icon"><i class="bi bi-bar-chart-line"></i></div>
                <h3>Relatorios</h3>
                <p>Relatorios consolidados para acompanhar a evolucao da igreja.</p>
            </div>
        </div>
    </div>
</section>

<!-- BENEFICIOS -->
<section class="landing-section alt" id="beneficios">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Beneficios</span>
            <h2 class="section-title">Por que igrejas escolhem o <span class="text-gradient">KADOSYS</span></h2>
        </div>

        <div class="beneficios-list">
            <div class="beneficio-row glass-card reveal">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Menos planilhas, mais clareza</h4>
                    <p>Substitua controles manuais por um sistema unico, organizado e automatizado.</p>
                </div>
            </div>
            <div class="beneficio-row glass-card reveal">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Dados exclusivos da sua igreja</h4>
                    <p>Cada instalacao utiliza seu proprio banco de dados, sem compartilhamento.</p>
                </div>
            </div>
            <div class="beneficio-row glass-card reveal">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Facil de usar</h4>
                    <p>Interface simples e moderna, pensada para secretarias, tesoureiros e lideranca.</p>
                </div>
            </div>
            <div class="beneficio-row glass-card reveal">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Pronto para o futuro</h4>
                    <p>Estrutura preparada para novos modulos e recursos de IA nas proximas etapas.</p>
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
            <h2 class="section-title">Conheca a interface do <span class="text-gradient">sistema</span></h2>
            <p class="section-lead">Imagens reais serao adicionadas conforme os modulos forem publicados.</p>
        </div>

        <div class="screens-grid">
            <div class="screen-placeholder glass-card reveal">
                <span class="glyph"><i class="bi bi-speedometer2"></i></span>
                Dashboard administrativo
            </div>
            <div class="screen-placeholder glass-card reveal">
                <span class="glyph"><i class="bi bi-people"></i></span>
                Cadastro de membros
            </div>
            <div class="screen-placeholder glass-card reveal">
                <span class="glyph"><i class="bi bi-cash-coin"></i></span>
                Painel financeiro
            </div>
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

        <div class="plans-grid">
            <div class="plan-card glass-card reveal">
                <div class="plan-icon"><i class="bi bi-seedling"></i></div>
                <h3>Essencial</h3>
                <p class="plan-desc">Para igrejas que estao comecando a organizar sua gestao.</p>
                <div class="plan-price">R$ 97<span>/mes</span></div>
                <ul class="plan-features">
                    <li class="plan-feature"><i class="bi bi-check2"></i> Gestao de membros</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Agenda e cultos</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Projecao/Telao (Biblia e video)</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> 1 usuario administrador</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Suporte por e-mail</li>
                </ul>
                <a href="<?= $basePath ?>/login" class="btn-k btn-k-outline">Comecar agora</a>
            </div>

            <div class="plan-card glass-card featured reveal">
                <span class="badge-featured">Mais escolhido</span>
                <div class="plan-icon"><i class="bi bi-stars"></i></div>
                <h3>Premium</h3>
                <p class="plan-desc">Para igrejas com ministerios e financeiro ativos.</p>
                <div class="plan-price">R$ 197<span>/mes</span></div>
                <ul class="plan-features">
                    <li class="plan-feature"><i class="bi bi-check2"></i> Tudo do plano Essencial</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Ministerios e grupos</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Financeiro completo</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Comunicacao e avisos</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Usuarios ilimitados</li>
                </ul>
                <a href="<?= $basePath ?>/login" class="btn-k btn-k-grad">Comecar agora</a>
            </div>

            <div class="plan-card glass-card reveal">
                <div class="plan-icon"><i class="bi bi-building"></i></div>
                <h3>Enterprise</h3>
                <p class="plan-desc">Para redes de igrejas e estruturas administrativas maiores.</p>
                <div class="plan-price">Sob consulta</div>
                <ul class="plan-features">
                    <li class="plan-feature"><i class="bi bi-check2"></i> Tudo do plano Premium</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Patrimonio e relatorios avancados</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Permissoes avancadas por perfil</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Atendimento dedicado</li>
                </ul>
                <a href="<?= $basePath ?>/login" class="btn-k btn-k-outline">Comecar agora</a>
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
                            <td class="plan-compare-feature-col"><i class="bi bi-person-badge"></i> Usuarios administradores</td>
                            <td>1</td>
                            <td>Ilimitados</td>
                            <td>Ilimitados</td>
                        </tr>
                        <tr>
                            <td class="plan-compare-feature-col"><i class="bi bi-headset"></i> Suporte</td>
                            <td>E-mail</td>
                            <td>E-mail prioritario</td>
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
            <h2 class="section-title">Ainda com <span class="text-gradient">duvidas?</span></h2>
        </div>

        <div class="faq-list">
            <div class="faq-item glass-card reveal" data-faq-item>
                <button class="faq-question">
                    Os dados da minha igreja sao compartilhados com outras igrejas?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>Nao. Cada instalacao do KADOSYS Igrejas utiliza seu proprio banco de dados,
                        exclusivo para a sua igreja, sem compartilhamento com outras instalacoes.</p>
                </div>
            </div>

            <div class="faq-item glass-card reveal" data-faq-item>
                <button class="faq-question">
                    Posso mudar de plano depois?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>Sim. E possivel alterar o plano contratado conforme a igreja cresce e novas
                        necessidades surgem.</p>
                </div>
            </div>

            <div class="faq-item glass-card reveal" data-faq-item>
                <button class="faq-question">
                    O sistema funciona em celular?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>Sim. A interface e responsiva e se adapta a celulares, tablets e computadores.</p>
                </div>
            </div>

            <div class="faq-item glass-card reveal" data-faq-item>
                <button class="faq-question">
                    Quais modulos estarao disponiveis em seguida?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>Modulos como Biblia, Louvores, Projecao, Streamer, CRM e ERP estao planejados
                        para sprints futuras de desenvolvimento.</p>
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
            <h2 class="section-title">Leve a gestao da sua igreja para <span class="text-gradient">o futuro</span></h2>
            <p>Acesse o sistema ou escolha um plano para comecar.</p>
            <div class="cta-final-actions">
                <a href="#planos" class="btn-k btn-k-grad">Comecar agora</a>
                <a href="<?= $basePath ?>/login" class="btn-k btn-k-outline">Acessar o sistema</a>
            </div>
        </div>
    </div>
</section>
