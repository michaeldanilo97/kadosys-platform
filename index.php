<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kadosys(TM) | Tecnologia para transformar processos em resultados</title>
    <meta name="description" content="A Kadosys(TM) desenvolve sistemas sob demanda e soluções SaaS com Inteligência Artificial e tecnologia de ponta.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://via.placeholder.com/32x32/0F172A/FFFFFF?text=K">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- AOS (Animate On Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark-main text-white">

    <!-- Loader -->
    <div id="loader" class="loader-wrapper" aria-live="polite" aria-label="Carregando site Kadosys(TM)">
        <div class="loader-orb" aria-hidden="true">
            <span class="loader-ring loader-ring-1"></span>
            <span class="loader-ring loader-ring-2"></span>
            <span class="loader-core"></span>
        </div>
        <div class="loader-content">
            <h2 class="loader-brand fw-bold tracking-widest">Kadosys<sup class="brand-tm">TM</sup></h2>
            <p class="loader-subtitle">Preparando soluções inteligentes</p>
            <div class="loader-progress" aria-hidden="true">
                <span></span>
            </div>
        </div>
    </div>

    <!-- Background Particles -->
    <div id="particles-js"></div>
    
    <!-- Cursor Glow -->
    <div id="cursor-glow"></div>

    <!-- Header -->
    <header class="header-fixed">
        <nav class="navbar navbar-expand-lg navbar-dark bg-transparent py-3">
            <div class="container">
                <a class="navbar-brand fw-bold fs-3" href="#">
                    <span class="text-gradient">Kadosys<sup class="brand-tm">TM</sup></span>
                </a>
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item"><a class="nav-link px-3" href="#inicio">Início</a></li>
                        <li class="nav-item"><a class="nav-link px-3" href="#sistemas">Sistemas</a></li>
                        <li class="nav-item"><a class="nav-link px-3" href="#sob-demanda">Sob Demanda</a></li>
                        <li class="nav-item"><a class="nav-link px-3" href="#sobre">Sobre</a></li>
                        <li class="nav-item"><a class="nav-link px-3" href="#contato">Contato</a></li>
                        <li class="nav-item ms-lg-3 mt-3 mt-lg-0 d-flex gap-2 flex-column flex-lg-row">
                            <a href="#" class="btn btn-outline-primary rounded-pill px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalAcesso">Acessar Sistema</a>
                            <a href="#solicitar" class="btn btn-primary-gradient rounded-pill px-4 py-2">Solicitar Sistema</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <!-- Hero Section -->
        <section id="inicio" class="hero-section d-flex align-items-center">
            <div class="container">
                <div class="row align-items-center min-vh-100">
                    <div class="col-lg-6" data-aos="fade-right">
                        <h1 class="display-3 fw-extrabold mb-4">
                            Desenvolvemos sistemas <span class="text-gradient">inteligentes</span> para acelerar seu negócio
                        </h1>
                        <p class="lead text-gray-400 mb-5">
                            Transformamos ideias em soluções digitais com tecnologia moderna, automação e inteligência artificial.
                        </p>
                        <div class="d-flex flex-column flex-sm-row gap-3">
                            <a href="#sistemas" class="btn btn-primary-gradient btn-lg rounded-pill px-5">Conhecer Sistemas</a>
                            <a href="#solicitar" class="btn btn-outline-light btn-lg rounded-pill px-5">Solicitar Projeto</a>
                        </div>
                    </div>
                    <div class="col-lg-6 mt-5 mt-lg-0" data-aos="fade-left">
                        <div class="hero-image-wrapper">
                            <div class="glass-card dashboard-preview">
                                <img src="assets/hero-dashboard.jpg" alt="Interface Futurista" class="img-fluid rounded-4 shadow-glow">
                                <div class="floating-badge badge-1">
                                    <i class="fas fa-brain me-2"></i> IA Ativa
                                </div>
                                <div class="floating-badge badge-2">
                                    <i class="fas fa-chart-line me-2"></i> +85% Eficiência
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-5 bg-dark-2">
            <div class="container">
                <div class="row text-center">
                    <div class="col-md-3 col-6 mb-4 mb-md-0" data-aos="zoom-in">
                        <h2 class="counter fw-bold text-gradient" data-target="150">0</h2>
                        <p class="text-gray-400">Projetos Entregues</p>
                    </div>
                    <div class="col-md-3 col-6 mb-4 mb-md-0" data-aos="zoom-in" data-aos-delay="100">
                        <h2 class="counter fw-bold text-gradient" data-target="50">0</h2>
                        <p class="text-gray-400">Clientes Ativos</p>
                    </div>
                    <div class="col-md-3 col-6 mb-4 mb-md-0" data-aos="zoom-in" data-aos-delay="200">
                        <h2 class="counter fw-bold text-gradient" data-target="12">0</h2>
                        <p class="text-gray-400">Sistemas SaaS</p>
                    </div>
                    <div class="col-md-3 col-6 mb-4 mb-md-0" data-aos="zoom-in" data-aos-delay="300">
                        <h2 class="counter fw-bold text-gradient" data-target="99">0</h2>
                        <p class="text-gray-400">% Satisfação</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Nossos Sistemas Section -->
        <section id="sistemas" class="py-100">
            <div class="container">
                <div class="text-center mb-5" data-aos="fade-up">
                    <h2 class="display-5 fw-bold mb-3">Nossos <span class="text-gradient">Sistemas</span></h2>
                    <p class="text-gray-400 max-w-600 mx-auto">Soluções prontas e robustas para diversos segmentos do mercado.</p>
                </div>
                <div class="row g-4">
                    <!-- Sistema 1 -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="0">
                        <div class="glass-card h-100 p-4 card-hover">
                            <div class="icon-box mb-4">
                                <i class="fas fa-church fs-2 text-primary"></i>
                            </div>
                            <h3 class="h4 fw-bold mb-3">Kadosys<sup class="brand-tm">TM</sup> Igrejas</h3>
                            <p class="text-gray-400 mb-4">Gestão completa para igrejas, projeção, membros e ministérios.</p>
                            <a href="apps/igrejas/public/" class="btn btn-link text-primary p-0 text-decoration-none fw-semibold">Conhecer <i class="fas fa-arrow-right ms-2"></i></a>
                        </div>
                    </div>
                    <!-- Sistema 2 -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="glass-card h-100 p-4 card-hover">
                            <div class="icon-box mb-4">
                                <i class="fas fa-headset fs-2 text-primary"></i>
                            </div>
                            <h3 class="h4 fw-bold mb-3">Kadosys<sup class="brand-tm">TM</sup> Stream</h3>
                            <p class="text-gray-400 mb-4">Plataforma de streaming de jogos com ultra baixa latência e alta performance.</p>
                            <a href="#" class="btn btn-link text-primary p-0 text-decoration-none fw-semibold">Saiba Mais <i class="fas fa-arrow-right ms-2"></i></a>
                        </div>
                    </div>
                    <!-- Sistema 3 -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="glass-card h-100 p-4 card-hover">
                            <div class="icon-box mb-4">
                                <i class="fas fa-users-cog fs-2 text-primary"></i>
                            </div>
                            <h3 class="h4 fw-bold mb-3">Kadosys<sup class="brand-tm">TM</sup> CRM</h3>
                            <p class="text-gray-400 mb-4">Controle comercial avançado e relacionamento inteligente com clientes.</p>
                            <a href="#" class="btn btn-link text-primary p-0 text-decoration-none fw-semibold">Saiba Mais <i class="fas fa-arrow-right ms-2"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Streaming Section -->
        <section class="py-100 bg-dark-2 overflow-hidden">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right">
                        <h2 class="display-5 fw-bold mb-4">Infraestrutura para <span class="text-gradient">Streaming de Jogos</span></h2>
                        <p class="text-gray-400 mb-4">Desenvolvemos soluções de ponta para streamers e empresas de gaming. Tecnologia de ultra baixa latência para uma experiência imersiva.</p>
                        <ul class="list-unstyled mb-5 row g-3">
                            <li class="col-6"><i class="fas fa-check-circle text-primary me-2"></i> Latência Zero</li>
                            <li class="col-6"><i class="fas fa-check-circle text-primary me-2"></i> Qualidade 4K/60fps</li>
                            <li class="col-6"><i class="fas fa-check-circle text-primary me-2"></i> Integração Social</li>
                            <li class="col-6"><i class="fas fa-check-circle text-primary me-2"></i> Dashboards em Tempo Real</li>
                            <li class="col-6"><i class="fas fa-check-circle text-primary me-2"></i> Servidores Globais</li>
                            <li class="col-6"><i class="fas fa-check-circle text-primary me-2"></i> Suporte 24/7</li>
                        </ul>
                        <a href="#solicitar" class="btn btn-primary-gradient btn-lg rounded-pill px-5">Solicitar Orçamento</a>
                    </div>
                    <div class="col-lg-6" data-aos="fade-left">
                        <div class="hero-image-wrapper">
                            <div class="glass-card p-2">
                                <img src="assets/gaming-streaming.jpg" alt="Gaming Streaming" class="img-fluid rounded-4 shadow-glow">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Process Section -->
        <section class="py-100">
            <div class="container">
                <div class="text-center mb-5" data-aos="fade-up">
                    <h2 class="display-5 fw-bold mb-3">Nosso <span class="text-gradient">Processo</span></h2>
                    <p class="text-gray-400 max-w-600 mx-auto">Como transformamos sua necessidade em uma solução tecnológica.</p>
                </div>
                <div class="timeline">
                    <div class="timeline-item" data-aos="fade-up">
                        <div class="timeline-dot">1</div>
                        <div class="timeline-content glass-card p-4">
                            <h4 class="fw-bold">Entendimento da necessidade</h4>
                            <p class="text-gray-400 m-0">Analisamos profundamente seus desafios e objetivos de negócio.</p>
                        </div>
                    </div>
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="100">
                        <div class="timeline-dot">2</div>
                        <div class="timeline-content glass-card p-4">
                            <h4 class="fw-bold">Planejamento</h4>
                            <p class="text-gray-400 m-0">Desenhamos a arquitetura, UX/UI e o roadmap do projeto.</p>
                        </div>
                    </div>
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="200">
                        <div class="timeline-dot">3</div>
                        <div class="timeline-content glass-card p-4">
                            <h4 class="fw-bold">Desenvolvimento</h4>
                            <p class="text-gray-400 m-0">Codificação ágil com as melhores tecnologias do mercado.</p>
                        </div>
                    </div>
                    <div class="timeline-item" data-aos="fade-up" data-aos-delay="300">
                        <div class="timeline-dot">4</div>
                        <div class="timeline-content glass-card p-4">
                            <h4 class="fw-bold">Implantação</h4>
                            <p class="text-gray-400 m-0">Entrega, treinamento e acompanhamento da solução em produção.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Diferenciais Section -->
        <section class="py-100 bg-dark-2">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-4 col-md-6" data-aos="zoom-in">
                        <div class="feature-card text-center p-4">
                            <div class="feature-icon mb-4"><i class="fas fa-bolt text-primary fs-1"></i></div>
                            <h4 class="fw-bold">Desenvolvimento Rápido</h4>
                            <p class="text-gray-400">Metodologias ágeis para entregar valor no menor tempo possível.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="100">
                        <div class="feature-card text-center p-4">
                            <div class="feature-icon mb-4"><i class="fas fa-robot text-primary fs-1"></i></div>
                            <h4 class="fw-bold">Integração com IA</h4>
                            <p class="text-gray-400">Sistemas inteligentes que aprendem e automatizam tarefas complexas.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="200">
                        <div class="feature-card text-center p-4">
                            <div class="feature-icon mb-4"><i class="fas fa-cloud text-primary fs-1"></i></div>
                            <h4 class="fw-bold">Sistemas em Nuvem</h4>
                            <p class="text-gray-400">Acesse seus dados de qualquer lugar com infraestrutura escalável.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Solicitar Sistema Section -->
        <section id="solicitar" class="py-100 contact-section">
            <div class="container">
                <div class="row align-items-stretch g-4">
                    <div class="col-lg-5" data-aos="fade-right">
                        <div class="contact-card contact-info-card h-100">
                            <span class="contact-kicker">Vamos tirar sua ideia do papel</span>
                            <h2 class="display-5 fw-bold mb-4">Solicitar <span class="text-gradient">Sistema</span></h2>
                            <p class="text-gray-400 mb-4">Conte rapidamente o que você precisa. Nossa equipe analisa o cenário e retorna com o melhor caminho para desenvolver ou adaptar sua solução.</p>

                            <div class="contact-benefits">
                                <div class="contact-benefit">
                                    <span><i class="fas fa-comments"></i></span>
                                    <div>
                                        <strong>Diagnóstico objetivo</strong>
                                        <small>Entendemos sua demanda antes de sugerir tecnologia.</small>
                                    </div>
                                </div>
                                <div class="contact-benefit">
                                    <span><i class="fas fa-diagram-project"></i></span>
                                    <div>
                                        <strong>Escopo sob medida</strong>
                                        <small>SaaS, integrações, IA ou desenvolvimento personalizado.</small>
                                    </div>
                                </div>
                                <div class="contact-benefit">
                                    <span><i class="fas fa-clock"></i></span>
                                    <div>
                                        <strong>Retorno rápido</strong>
                                        <small>Entraremos em contato para alinhar os próximos passos.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7" data-aos="fade-left">
                        <div class="contact-card contact-form-card h-100">
                            <div class="mb-4">
                                <h3 class="h2 fw-bold mb-2">Dados do projeto</h3>
                                <p class="text-gray-400 mb-0">Preencha os campos abaixo para receber uma proposta inicial.</p>
                            </div>

                            <form id="contactForm" class="contact-form">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label-modern" for="name">Nome completo</label>
                                        <div class="input-shell">
                                            <i class="fas fa-user"></i>
                                            <input type="text" class="form-control" id="name" placeholder="Seu nome" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-modern" for="company">Empresa</label>
                                        <div class="input-shell">
                                            <i class="fas fa-building"></i>
                                            <input type="text" class="form-control" id="company" placeholder="Nome da empresa" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-modern" for="whatsapp">WhatsApp</label>
                                        <div class="input-shell">
                                            <i class="fab fa-whatsapp"></i>
                                            <input type="tel" class="form-control" id="whatsapp" placeholder="(00) 00000-0000" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-modern" for="email">E-mail corporativo</label>
                                        <div class="input-shell">
                                            <i class="fas fa-envelope"></i>
                                            <input type="email" class="form-control" id="email" placeholder="voce@empresa.com" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label-modern" for="systemType">Tipo de solução</label>
                                        <div class="input-shell select-shell">
                                            <i class="fas fa-layer-group"></i>
                                            <select class="form-select" id="systemType" required>
                                                <option value="" selected disabled>Selecione uma opção</option>
                                                <option value="saas">Sistema SaaS Pronto</option>
                                                <option value="sob-demanda">Desenvolvimento Sob Demanda</option>
                                                <option value="ia">Integração de IA</option>
                                                <option value="gaming-streaming">Gaming & Streaming</option>
                                                <option value="outros">Outros</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label-modern" for="message">Descreva sua necessidade</label>
                                        <div class="input-shell textarea-shell">
                                            <i class="fas fa-pen-to-square"></i>
                                            <textarea class="form-control" placeholder="Ex.: preciso automatizar processos, criar um painel, integrar IA..." id="message" rows="5"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex flex-column flex-sm-row align-items-sm-center gap-3 mt-4">
                                        <button type="submit" class="btn btn-primary-gradient btn-lg rounded-pill px-5 py-3">Enviar Solicitação</button>
                                        <small class="text-gray-500">Responderemos pelo WhatsApp ou e-mail informado.</small>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-100">
            <div class="container">
                <div class="text-center mb-5" data-aos="fade-up">
                    <h2 class="display-5 fw-bold mb-3">Perguntas <span class="text-gradient">Frequentes</span></h2>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-8" data-aos="fade-up">
                        <div class="accordion accordion-flush custom-accordion" id="faqAccordion">
                            <div class="accordion-item glass-card mb-3 border-0">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed bg-transparent text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        Como funciona o desenvolvimento sob demanda?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-gray-400">
                                        Iniciamos com uma reunião de diagnóstico para entender suas necessidades. Em seguida, apresentamos um plano de projeto e orçamento. Após a aprovação, iniciamos o desenvolvimento ágil com entregas incrementais.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item glass-card mb-3 border-0">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed bg-transparent text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Os sistemas SaaS podem ser personalizados?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-gray-400">
                                        Sim! Nossos sistemas SaaS possuem diversos módulos configuráveis e oferecemos a possibilidade de customizações específicas para atender fluxos únicos da sua empresa.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal Acesso -->
    <div class="modal fade" id="modalAcesso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Acessar <span class="text-gradient">Sistemas Kadosys<sup class="brand-tm">TM</sup></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="list-group list-group-flush bg-transparent">
                        <a href="apps/igrejas/public/" class="list-group-item list-group-item-action bg-transparent text-white border-secondary border-opacity-25 py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-church text-primary me-3"></i>
                                <span>Kadosys<sup class="brand-tm">TM</sup> Igrejas</span>
                            </div>
                            <small class="text-gray-500">igrejas.kadosys.com.br</small>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action bg-transparent text-white border-secondary border-opacity-25 py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-users-cog text-primary me-3"></i>
                                <span>Kadosys<sup class="brand-tm">TM</sup> CRM</span>
                            </div>
                            <small class="text-gray-500">crm.kadosys.com.br</small>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-headset text-primary me-3"></i>
                                <span>Kadosys<sup class="brand-tm">TM</sup> Stream</span>
                            </div>
                            <small class="text-primary">Acessar</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-5 bg-dark-footer border-top border-secondary border-opacity-10">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <a class="navbar-brand fw-bold fs-3 mb-4 d-block" href="#">
                        <span class="text-gradient">Kadosys<sup class="brand-tm">TM</sup></span>
                    </a>
                    <p class="text-gray-400 mb-4">Tecnologia para transformar processos em resultados. Líder em soluções inteligentes e sistemas personalizados para o futuro do seu negócio.</p>
                    <div class="social-links d-flex gap-3">
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h5 class="fw-bold mb-4">Links Rápidos</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="#inicio">Início</a></li>
                        <li><a href="#sistemas">Sistemas</a></li>
                        <li><a href="#sob-demanda">Sob Demanda</a></li>
                        <li><a href="#sobre">Sobre Nós</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h5 class="fw-bold mb-4">Nossos Sistemas</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="apps/igrejas/public/">Kadosys<sup class="brand-tm">TM</sup> Igrejas</a></li>
                        <li><a href="#">Kadosys<sup class="brand-tm">TM</sup> CRM</a></li>
                        <li><a href="#">Kadosys<sup class="brand-tm">TM</sup> Financeiro</a></li>
                        <li><a href="#">Kadosys<sup class="brand-tm">TM</sup> Frota</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h5 class="fw-bold mb-4">Contato</h5>
                    <ul class="list-unstyled footer-contact">
                        <li><i class="fas fa-envelope text-primary me-2"></i> contato@kadosys.com.br</li>
                        <li><i class="fab fa-whatsapp text-primary me-2"></i> (11) 99999-9999</li>
                        <li><i class="fas fa-map-marker-alt text-primary me-2"></i> São Paulo, SP</li>
                    </ul>
                </div>
            </div>
            <hr class="my-5 border-secondary border-opacity-10">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-gray-500 m-0">&copy; 2026 Kadosys<sup class="brand-tm">TM</sup> Tecnologia. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/5511999999999" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="script.js"></script>
</body>
</html>
