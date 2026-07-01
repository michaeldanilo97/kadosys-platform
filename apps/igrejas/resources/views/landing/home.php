<?php
/**
 * @var array $config
 */
$basePath = $config['base_path'] ?? '';
?>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div>
            <div class="hero-folio">Folio 01 &middot; KADOSYS Igrejas</div>
            <h1>A administracao da sua igreja, <em>organizada como deveria ser</em>.</h1>
            <p class="lead">
                Membros, ministerios, cultos, agenda, financeiro e patrimonio em um unico sistema,
                pensado para a rotina real de quem cuida da gestao da igreja.
            </p>
            <div class="hero-actions">
                <a href="#planos" class="btn-kadosys btn-gold-kadosys">Comecar agora</a>
                <a href="<?= $basePath ?>/login" class="btn-kadosys btn-primary-kadosys">
                    Acessar o sistema <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="hero-meta">
                <div><strong>12</strong> modulos integrados</div>
                <div><strong>100%</strong> dados na sua propria base</div>
                <div><strong>1</strong> sistema, toda a igreja</div>
            </div>
        </div>

        <div class="hero-panel">
            <div class="hero-panel-header">
                <span>livro de registro &middot; visao geral</span>
                <div class="hero-panel-dots"><span></span><span></span><span></span></div>
            </div>
            <div class="hero-panel-body">
                <div class="hero-row">
                    <span>Culto de domingo - 10h</span>
                    <span class="tag">agendado</span>
                </div>
                <div class="hero-row">
                    <span>Novos membros este mes</span>
                    <span class="tag gold">+18</span>
                </div>
                <div class="hero-row">
                    <span>Ministerio de louvor</span>
                    <span class="tag">14 voluntarios</span>
                </div>
                <div class="hero-row">
                    <span>Dizimos e ofertas</span>
                    <span class="tag gold">atualizado</span>
                </div>
                <div class="hero-row">
                    <span>Reuniao de lideranca</span>
                    <span class="tag">quarta - 19h30</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SOBRE -->
<section class="landing-section" id="sobre">
    <div class="container">
        <div class="section-header">
            <div class="folio-label"><span class="num">Folio 02</span> &middot; Sobre o sistema</div>
            <h2 class="section-title">Um sistema feito para a rotina da igreja, nao para se adaptar a ela</h2>
            <p class="section-lead">
                O KADOSYS Igrejas reune, em um unico lugar, os processos que normalmente se perdem entre
                planilhas, papeis e grupos de mensagens: cadastro de membros, organizacao de ministerios,
                controle financeiro e comunicacao com a congregacao.
            </p>
        </div>

        <div class="sobre-grid">
            <div class="sobre-item">
                <h3>Centralizado</h3>
                <p>Toda a informacao da igreja em um unico sistema, acessivel de qualquer lugar.</p>
            </div>
            <div class="sobre-item">
                <h3>Seguro</h3>
                <p>Cada igreja com seu proprio banco de dados, autenticacao protegida e controle de acesso.</p>
            </div>
            <div class="sobre-item">
                <h3>Pensado para crescer</h3>
                <p>Estrutura preparada para novos modulos, conforme a igreja avanca em sua gestao.</p>
            </div>
        </div>
    </div>
</section>

<!-- RECURSOS -->
<section class="landing-section alt" id="recursos">
    <div class="container">
        <div class="section-header">
            <div class="folio-label"><span class="num">Folio 03</span> &middot; Recursos</div>
            <h2 class="section-title">Tudo que a secretaria, a tesouraria e a lideranca precisam</h2>
            <p class="section-lead">
                Recursos pensados para reduzir o trabalho manual e dar visibilidade sobre o que importa.
            </p>
        </div>

        <div class="cards-grid">
            <div class="feature-card">
                <div class="icon"><i class="bi bi-people"></i></div>
                <h3>Gestao de membros</h3>
                <p>Cadastro completo, historico e acompanhamento de cada membro da congregacao.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="bi bi-diagram-3"></i></div>
                <h3>Ministerios e grupos</h3>
                <p>Organizacao de ministerios, pequenos grupos e suas respectivas liderancas.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="bi bi-cash-coin"></i></div>
                <h3>Financeiro</h3>
                <p>Controle de dizimos, ofertas e despesas com relatorios claros.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="bi bi-calendar3"></i></div>
                <h3>Agenda e cultos</h3>
                <p>Programacao de cultos, eventos e compromissos da igreja em um calendario unico.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="bi bi-building"></i></div>
                <h3>Patrimonio</h3>
                <p>Controle de bens, imoveis e equipamentos sob responsabilidade da igreja.</p>
            </div>
            <div class="feature-card">
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
        <div class="section-header">
            <div class="folio-label"><span class="num">Folio 04</span> &middot; Funcionalidades</div>
            <h2 class="section-title">Um painel administrativo completo</h2>
            <p class="section-lead">
                Dashboard moderno, com visao clara dos indicadores e navegacao simples entre os modulos.
            </p>
        </div>

        <div class="cards-grid">
            <div class="feature-card">
                <div class="icon"><i class="bi bi-speedometer2"></i></div>
                <h3>Dashboard com indicadores</h3>
                <p>Visao geral da igreja com numeros atualizados dos principais modulos.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="bi bi-shield-lock"></i></div>
                <h3>Usuarios e permissoes</h3>
                <p>Controle de quem acessa cada area do sistema, por perfil de usuario.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="bi bi-moon-stars"></i></div>
                <h3>Modo claro e escuro</h3>
                <p>Interface adaptavel a preferencia visual de cada usuario.</p>
            </div>
            <div class="feature-card">
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
        <div class="section-header">
            <div class="folio-label"><span class="num">Folio 05</span> &middot; Beneficios</div>
            <h2 class="section-title">Por que igrejas escolhem o KADOSYS</h2>
        </div>

        <div class="beneficios-list">
            <div class="beneficio-row">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Menos planilhas, mais clareza</h4>
                    <p>Substitua controles manuais por um sistema unico e organizado.</p>
                </div>
            </div>
            <div class="beneficio-row">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Dados exclusivos da sua igreja</h4>
                    <p>Cada instalacao utiliza seu proprio banco de dados, sem compartilhamento.</p>
                </div>
            </div>
            <div class="beneficio-row">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Facil de usar</h4>
                    <p>Interface simples, pensada para secretarias, tesoureiros e lideranca.</p>
                </div>
            </div>
            <div class="beneficio-row">
                <div class="check"><i class="bi bi-check-lg"></i></div>
                <div>
                    <h4>Pronto para crescer</h4>
                    <p>Estrutura preparada para receber novos modulos nas proximas etapas.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CAPTURAS DE TELA -->
<section class="landing-section" id="capturas">
    <div class="container">
        <div class="section-header">
            <div class="folio-label"><span class="num">Folio 06</span> &middot; Capturas de tela</div>
            <h2 class="section-title">Conheca a interface do sistema</h2>
            <p class="section-lead">Imagens reais serao adicionadas conforme os modulos forem publicados.</p>
        </div>

        <div class="screens-grid">
            <div class="screen-placeholder">
                <span class="glyph"><i class="bi bi-speedometer2"></i></span>
                Dashboard administrativo
            </div>
            <div class="screen-placeholder">
                <span class="glyph"><i class="bi bi-people"></i></span>
                Cadastro de membros
            </div>
            <div class="screen-placeholder">
                <span class="glyph"><i class="bi bi-cash-coin"></i></span>
                Painel financeiro
            </div>
        </div>
    </div>
</section>

<!-- PLANOS -->
<section class="landing-section alt" id="planos">
    <div class="container">
        <div class="section-header">
            <div class="folio-label"><span class="num">Folio 07</span> &middot; Planos</div>
            <h2 class="section-title">Escolha o plano ideal para sua igreja</h2>
            <p class="section-lead">Planos pensados para igrejas de diferentes tamanhos e necessidades.</p>
        </div>

        <div class="plans-grid">
            <div class="plan-card">
                <h3>Essencial</h3>
                <p class="plan-desc">Para igrejas que estao comecando a organizar sua gestao.</p>
                <div class="plan-price">R$ 97<span>/mes</span></div>
                <ul class="plan-features">
                    <li class="plan-feature"><i class="bi bi-check2"></i> Gestao de membros</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Agenda e cultos</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> 1 usuario administrador</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Suporte por e-mail</li>
                </ul>
                <a href="<?= $basePath ?>/login" class="btn-kadosys btn-outline-kadosys">Comecar agora</a>
            </div>

            <div class="plan-card featured">
                <span class="badge-featured">Mais escolhido</span>
                <h3>Premium</h3>
                <p class="plan-desc">Para igrejas com ministerios e financeiro ativos.</p>
                <div class="plan-price">R$ 197<span>/mes</span></div>
                <ul class="plan-features">
                    <li class="plan-feature"><i class="bi bi-check2"></i> Tudo do plano Essencial</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Ministerios e grupos</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Financeiro completo</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Usuarios ilimitados</li>
                </ul>
                <a href="<?= $basePath ?>/login" class="btn-kadosys btn-gold-kadosys">Comecar agora</a>
            </div>

            <div class="plan-card">
                <h3>Enterprise</h3>
                <p class="plan-desc">Para redes de igrejas e estruturas administrativas maiores.</p>
                <div class="plan-price">Sob consulta</div>
                <ul class="plan-features">
                    <li class="plan-feature"><i class="bi bi-check2"></i> Tudo do plano Premium</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Patrimonio e relatorios avancados</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Permissoes avancadas por perfil</li>
                    <li class="plan-feature"><i class="bi bi-check2"></i> Atendimento dedicado</li>
                </ul>
                <a href="<?= $basePath ?>/login" class="btn-kadosys btn-outline-kadosys">Comecar agora</a>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="landing-section" id="faq">
    <div class="container">
        <div class="section-header">
            <div class="folio-label"><span class="num">Folio 08</span> &middot; Perguntas frequentes</div>
            <h2 class="section-title">Ainda com duvidas?</h2>
        </div>

        <div class="faq-list">
            <div class="faq-item" data-faq-item>
                <button class="faq-question">
                    Os dados da minha igreja sao compartilhados com outras igrejas?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>Nao. Cada instalacao do KADOSYS Igrejas utiliza seu proprio banco de dados,
                        exclusivo para a sua igreja, sem compartilhamento com outras instalacoes.</p>
                </div>
            </div>

            <div class="faq-item" data-faq-item>
                <button class="faq-question">
                    Posso mudar de plano depois?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>Sim. E possivel alterar o plano contratado conforme a igreja cresce e novas
                        necessidades surgem.</p>
                </div>
            </div>

            <div class="faq-item" data-faq-item>
                <button class="faq-question">
                    O sistema funciona em celular?
                    <span class="plus">+</span>
                </button>
                <div class="faq-answer">
                    <p>Sim. A interface e responsiva e se adapta a celulares, tablets e computadores.</p>
                </div>
            </div>

            <div class="faq-item" data-faq-item>
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
        <h2 class="section-title">Comece a organizar a gestao da sua igreja hoje</h2>
        <p>Acesse o sistema ou escolha um plano para comecar.</p>
        <div class="cta-final-actions">
            <a href="#planos" class="btn-kadosys btn-gold-kadosys">Comecar agora</a>
            <a href="<?= $basePath ?>/login" class="btn-kadosys btn-outline-kadosys" style="border-color: rgba(246,243,234,0.4); color: var(--paper);">
                Acessar o sistema
            </a>
        </div>
    </div>
</section>
