<?php

declare(strict_types=1);

use Igrejas\Core\Csrf;
use Igrejas\Core\TenantResolver;
use Igrejas\Core\View;
use Igrejas\Models\ComunicacaoAviso;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Culto;
use Igrejas\Models\FaturaPix;
use Igrejas\Models\Plano;
use Igrejas\Models\PlataformaAviso;
use Igrejas\Models\User;

/**
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 * @var string $activeMenu
 * @var array $breadcrumb
 * @var \Igrejas\Models\User|null $user
 * @var array $modules
 */
$basePath = $config['base_path'] ?? '';
$userName = $user?->name ?? 'Usuário';
$userInitial = mb_strtoupper(mb_substr($userName, 0, 1));
$planoAtual = ConfiguracaoIgreja::atual()->plano;
$emTrial = TenantResolver::atual()?->metodoPagamento === 'trial';

// Quem pode ver informacao de plano/assinatura: admin (tela completa,
// em Configuracoes) ou quem tem acesso ao modulo Financeiro sem ser
// admin (tela so de consulta, ver FinanceiroController::plano()) - o
// resto da equipe (ex.: um voluntario so com acesso a Cultos) nao
// precisa nem ver avisos de cobranca que nao pode resolver.
$isAdmin = $user?->role === User::ROLE_ADMIN;
$temAcessoFinanceiro = User::podeAcessarModulo($user, 'financeiro');
$planoUrl = match (true) {
    $isAdmin => $basePath . '/dashboard/configuracoes#plano-contratado',
    $temAcessoFinanceiro => $basePath . '/dashboard/financeiro/plano',
    default => null,
};

// Aviso de fatura Pix perto do vencimento - so aparece pra tenants que
// pagam por Pix (ver AuthMiddleware pro bloqueio depois que vence). Nao
// mostra na propria tela de fatura vencida, que ja e dedicada a isso.
// Duas situacoes bem diferentes usam o mesmo aviso: a renovacao normal
// do plano atual, e um upgrade pra um plano diferente ainda nao pago -
// o texto muda pra deixar isso claro (ver AssinaturaController::iniciarPix).
$avisoFaturaPix = null;
$avisoFaturaPixTexto = '';
if ($activeMenu !== 'fatura-vencida') {
    $tenantAtual = TenantResolver::atual();

    if ($tenantAtual !== null && $tenantAtual->metodoPagamento === 'pix') {
        $ultimaFatura = FaturaPix::ultimaDoTenant($tenantAtual->id);

        if ($ultimaFatura !== null && $ultimaFatura->status === 'pendente') {
            $avisoFaturaPix = $ultimaFatura;
            $ehUpgrade = $ultimaFatura->plano !== $planoAtual;

            if ($ehUpgrade) {
                $vencimentoAtual = FaturaPix::ultimaPagaDoTenant($tenantAtual->id)?->vencimento;
                $avisoFaturaPixTexto = sprintf(
                    'Seu Pix para upgrade para o plano %s expira em %s. Seu plano atual é %s%s. Clique aqui para pagar o upgrade.',
                    Plano::label($ultimaFatura->plano),
                    (new DateTimeImmutable($ultimaFatura->vencimento))->format('d/m/Y \à\s H:i'),
                    Plano::label($planoAtual),
                    $vencimentoAtual !== null
                        ? ', com vencimento em ' . (new DateTimeImmutable($vencimentoAtual))->format('d/m/Y')
                        : ''
                );
            } else {
                $avisoFaturaPixTexto = sprintf(
                    'Sua fatura de renovação do plano %s vence em %s. Clique aqui para pagar com Pix.',
                    Plano::label($ultimaFatura->plano),
                    (new DateTimeImmutable($ultimaFatura->vencimento))->format('d/m/Y')
                );
            }
        }
    }
}

// Aviso de teste gratis (contagem regressiva) - so aparece pra tenants
// ainda em trial e nao vencido (depois de vencer, o AuthMiddleware ja
// bloqueia o resto do painel e manda pra /dashboard/trial-expirado).
$avisoTrialTexto = '';
if ($activeMenu !== 'trial-expirado' && $activeMenu !== 'fatura-vencida') {
    $tenantAtual ??= TenantResolver::atual();

    if ($tenantAtual !== null && $tenantAtual->metodoPagamento === 'trial' && $tenantAtual->trialExpiraEm !== null) {
        $expiraEm = new DateTimeImmutable($tenantAtual->trialExpiraEm);
        $agora = new DateTimeImmutable();

        if ($agora <= $expiraEm) {
            // Diferenca por DATA de calendario (sem a hora do dia) - com
            // timestamp bruto + ceil(), 1 dia e poucas horas de sobra
            // arredondava pra "2 dias", mesmo a data de vencimento sendo
            // literalmente amanha (ex.: hoje 10/07 as 13h, vence 11/07 -
            // e 1 dia de diferenca de calendario, nao 2).
            $diasRestantes = (int) $agora->setTime(0, 0, 0)->diff($expiraEm->setTime(0, 0, 0))->days;
            $avisoTrialTexto = sprintf(
                'Seu teste grátis termina em %d dia%s (%s). Clique aqui para escolher um plano.',
                $diasRestantes,
                $diasRestantes === 1 ? '' : 's',
                $expiraEm->format('d/m/Y')
            );
        }
    }
}

/**
 * Notificacoes do sino no topo do painel: junta os avisos de
 * pagamento/trial que ja existiam em outros pontos do layout com
 * avisos de Comunicacao publicados e o proximo culto agendado, pra dar
 * uma visao rapida sem precisar entrar em cada modulo. Cada item leva
 * a data mais relevante pra ele (publicacao, vencimento ou data do
 * evento) - a lista inteira e ordenada por essa data (mais recente
 * primeiro) antes de renderizar, em vez da ordem fixa em que os blocos
 * abaixo sao montados.
 *
 * @var array<int, array{icon: string, texto: string, url: string, data: DateTimeImmutable}>
 */
$notificacoes = [];

// Aviso do dono da plataforma (ver PlataformaAviso/PlataformaController)
// - vale pra todas as igrejas, independente de plano ou permissao, por
// isso vem antes de qualquer outro filtro. Filtrado por publico_alvo
// (admins/membros/todos, ver PlataformaAviso::ativoParaAudiencia()).
$avisoPlataforma = PlataformaAviso::ativoParaAudiencia($isAdmin);

if ($avisoPlataforma !== null) {
    $notificacoes[] = [
        'icon' => 'bi-broadcast-pin',
        'texto' => $avisoPlataforma->mensagem,
        'url' => '#',
        'data' => new DateTimeImmutable($avisoPlataforma->createdAt),
    ];
}

if ($avisoFaturaPixTexto !== '' && $avisoFaturaPix !== null) {
    $notificacoes[] = [
        'icon' => 'bi-qr-code',
        'texto' => $avisoFaturaPixTexto,
        'url' => $basePath . '/dashboard/fatura-vencida',
        'data' => new DateTimeImmutable($avisoFaturaPix->vencimento),
    ];
}

if ($avisoTrialTexto !== '' && $planoUrl !== null) {
    $notificacoes[] = [
        'icon' => 'bi-hourglass-split',
        'texto' => $avisoTrialTexto,
        'url' => $planoUrl,
        'data' => new DateTimeImmutable($tenantAtual->trialExpiraEm),
    ];
}

$comunicacaoDisponivel = Plano::disponivel($planoAtual, 'comunicacao', $emTrial) && User::podeAcessarModulo($user, 'comunicacao');

if ($comunicacaoDisponivel) {
    foreach (ComunicacaoAviso::ultimosPublicados(3) as $aviso) {
        $notificacoes[] = [
            'icon' => 'bi-megaphone',
            'texto' => $aviso->titulo,
            'url' => $basePath . '/dashboard/comunicacao',
            'data' => new DateTimeImmutable($aviso->dataPublicacao ?? $aviso->createdAt),
        ];
    }
}

if (User::podeAcessarModulo($user, 'cultos')) {
    $proximoCulto = Culto::proximoAgendado();

    if ($proximoCulto !== null) {
        $notificacoes[] = [
            'icon' => 'bi-calendar2-week',
            'texto' => $proximoCulto->titulo . ' - ' . $proximoCulto->dataHoraFormatada(),
            'url' => $basePath . '/dashboard/cultos',
            'data' => new DateTimeImmutable($proximoCulto->data . ' ' . ($proximoCulto->hora ?? '00:00:00')),
        ];
    }
}

usort($notificacoes, static fn (array $a, array $b): int => $b['data'] <=> $a['data']);

// Lista de avisos da barra lateral (substitui o antigo card "Assistente
// IA") - so aparece pra quem realmente tem acesso ao modulo Comunicacao.
// Cada aviso ganha um indicador de "novo" ate o usuario abrir a pagina
// de detalhe (ver ComunicacaoController::show()/marcarComoLido()).
$avisosSidebar = ($comunicacaoDisponivel && $user !== null)
    ? ComunicacaoAviso::paraSidebar($user->id, 5)
    : [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard - KADOSYS Igrejas', ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/dashboard.css?v=<?= View::assetVersion('assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/crud.css?v=<?= View::assetVersion('assets/css/crud.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/biblia-picker.css?v=<?= View::assetVersion('assets/css/biblia-picker.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/auth.css?v=<?= View::assetVersion('assets/css/auth.css') ?>">
</head>
<body class="dashboard-body">

<div class="dash-shell">
    <div class="sidebar-overlay" data-sidebar-overlay></div>

    <aside class="dash-sidebar" data-dash-sidebar>
        <div class="dash-sidebar-header">
            <a href="<?= $basePath ?>/dashboard" class="dash-sidebar-brand">
                <span class="seal">K</span>
                <span><span class="text-gradient">KADOSYS</span> Igrejas</span>
            </a>
            <button type="button" class="dash-sidebar-collapse-btn" data-sidebar-collapse aria-label="Recolher menu" title="Recolher menu">
                <i class="bi bi-chevron-left"></i>
            </button>
        </div>

        <nav class="dash-nav">
            <div class="dash-nav-group-label">Geral</div>
            <a href="<?= $basePath ?>/dashboard" class="dash-nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> <span class="dash-nav-link-label">Dashboard</span>
            </a>
            <?php if ($user !== null): ?>
                <a href="<?= $basePath ?>/dashboard/perfil" class="dash-nav-link <?= $activeMenu === 'perfil' ? 'active' : '' ?>">
                    <?php if ($user->fotoPath !== null): ?>
                        <img src="<?= $basePath ?>/<?= htmlspecialchars($user->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="" class="dash-nav-avatar">
                    <?php else: ?>
                        <i class="bi bi-person-circle"></i>
                    <?php endif; ?>
                    <span class="dash-nav-link-label">Meu perfil</span>
                </a>
            <?php endif; ?>

            <?php
            // Modulos usados AO VIVO durante o culto (telao pro operador
            // e cifras pro time de louvor) - destacados no topo do menu,
            // separados dos demais, pra achar rapido em cima da hora.
            $modulosAoVivo = ['projecao', 'louvores'];
            ?>
            <div class="dash-nav-group-label">Ao vivo</div>
            <?php foreach ($modulosAoVivo as $slug): ?>
                <?php if (!isset($modules[$slug])) continue; ?>
                <?php
                $module = $modules[$slug];
                $bloqueadoPeloPlano = !Plano::disponivel($planoAtual, $slug, $emTrial);
                $bloqueadoPelaPermissao = !$bloqueadoPeloPlano && !User::podeAcessarModulo($user, $slug);
                $bloqueado = $bloqueadoPeloPlano || $bloqueadoPelaPermissao;
                ?>
                <a href="<?= $basePath ?>/dashboard/<?= $slug ?>" class="dash-nav-link dash-nav-link-live <?= $activeMenu === $slug ? 'active' : '' ?><?= $bloqueado ? ' dash-nav-link-locked' : '' ?>">
                    <i class="bi <?= htmlspecialchars($module['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    <span class="dash-nav-link-label"><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($bloqueadoPeloPlano): ?>
                        <i class="bi bi-lock-fill dash-nav-lock-icon" title="Disponível no plano <?= htmlspecialchars(Plano::label($module['planoMinimo']), ENT_QUOTES, 'UTF-8') ?>"></i>
                    <?php elseif ($bloqueadoPelaPermissao): ?>
                        <i class="bi bi-shield-lock dash-nav-lock-icon" title="Sem permissão pra acessar este módulo"></i>
                    <?php else: ?>
                        <span class="dash-nav-live-dot" title="Módulo usado ao vivo durante o culto"></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <div class="dash-nav-group-label">Módulos</div>
            <?php foreach ($modules as $slug => $module): ?>
                <?php if (in_array($slug, $modulosAoVivo, true)) continue; ?>
                <?php
                $bloqueadoPeloPlano = !Plano::disponivel($planoAtual, $slug, $emTrial);
                $bloqueadoPelaPermissao = !$bloqueadoPeloPlano && !User::podeAcessarModulo($user, $slug);
                $bloqueado = $bloqueadoPeloPlano || $bloqueadoPelaPermissao;
                ?>
                <a href="<?= $basePath ?>/dashboard/<?= $slug ?>" class="dash-nav-link <?= $activeMenu === $slug ? 'active' : '' ?><?= $bloqueado ? ' dash-nav-link-locked' : '' ?>">
                    <i class="bi <?= htmlspecialchars($module['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    <span class="dash-nav-link-label"><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($bloqueadoPeloPlano): ?>
                        <i class="bi bi-lock-fill dash-nav-lock-icon" title="Disponível no plano <?= htmlspecialchars(Plano::label($module['planoMinimo']), ENT_QUOTES, 'UTF-8') ?>"></i>
                    <?php elseif ($bloqueadoPelaPermissao): ?>
                        <i class="bi bi-shield-lock dash-nav-lock-icon" title="Sem permissão pra acessar este módulo"></i>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="dash-sidebar-footer">
            <?php if ($comunicacaoDisponivel): ?>
                <div class="dash-avisos-card">
                    <div class="dash-avisos-title">
                        <i class="bi bi-megaphone"></i> Avisos
                        <a href="<?= $basePath ?>/dashboard/comunicacao" class="dash-avisos-ver-todos">Ver todos</a>
                    </div>

                    <?php if ($avisosSidebar === []): ?>
                        <p class="dash-avisos-vazio">Nenhum aviso publicado ainda.</p>
                    <?php else: ?>
                        <ul class="dash-avisos-list">
                            <?php foreach ($avisosSidebar as $avisoSidebar): ?>
                                <li>
                                    <a href="<?= $basePath ?>/dashboard/comunicacao/<?= $avisoSidebar->id ?>" class="dash-aviso-item">
                                        <?php if (!$avisoSidebar->lido): ?><span class="dash-aviso-dot" title="Não lido"></span><?php endif; ?>
                                        <span class="dash-aviso-titulo"><?= htmlspecialchars($avisoSidebar->titulo, ENT_QUOTES, 'UTF-8') ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= $basePath ?>/logout">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="dash-logout-btn">
                    <i class="bi bi-box-arrow-right"></i> Sair
                </button>
            </form>
        </div>
    </aside>

    <div class="dash-main">
        <header class="dash-topbar">
            <div class="dash-topbar-left">
                <button class="sidebar-toggle-btn" data-sidebar-open aria-label="Abrir menu">
                    <i class="bi bi-list"></i>
                </button>

                <div class="dash-breadcrumb">
                    <?php foreach ($breadcrumb as $index => $crumb): ?>
                        <?php if ($index > 0): ?><span class="sep">/</span><?php endif; ?>
                        <span class="<?= $index === count($breadcrumb) - 1 ? 'current' : '' ?>">
                            <?= htmlspecialchars($crumb, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dash-topbar-search">
                <i class="bi bi-search"></i>
                <input type="search" placeholder="Buscar membros, cultos, lançamentos..." aria-label="Buscar no sistema">
                <span class="kbd">/</span>
            </div>

            <div class="dash-topbar-right">
                <div class="topbar-dropdown" data-topbar-dropdown>
                    <button class="topbar-icon-btn" type="button" aria-label="Notificações" aria-expanded="false" data-dropdown-toggle>
                        <i class="bi bi-bell"></i>
                        <?php if ($notificacoes !== []): ?><span class="dot"></span><?php endif; ?>
                    </button>

                    <div class="topbar-dropdown-panel notif-panel" data-dropdown-panel hidden>
                        <div class="topbar-dropdown-head">Notificações</div>

                        <?php if ($notificacoes === []): ?>
                            <div class="notif-empty">Nenhuma notificação no momento.</div>
                        <?php else: ?>
                            <?php foreach ($notificacoes as $notificacao): ?>
                                <a href="<?= htmlspecialchars($notificacao['url'], ENT_QUOTES, 'UTF-8') ?>" class="notif-item">
                                    <i class="bi <?= htmlspecialchars($notificacao['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                    <span>
                                        <?= htmlspecialchars($notificacao['texto'], ENT_QUOTES, 'UTF-8') ?>
                                        <span class="notif-data"><?= $notificacao['data']->format('d/m/Y') ?></span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <button class="topbar-icon-btn" data-theme-toggle aria-label="Alternar tema claro/escuro">
                    <i class="bi bi-sun" data-theme-icon></i>
                </button>

                <div class="topbar-dropdown" data-topbar-dropdown>
                    <button class="topbar-user" type="button" aria-expanded="false" data-dropdown-toggle>
                        <span class="avatar">
                            <?php if ($user?->fotoPath !== null): ?>
                                <img src="<?= $basePath ?>/<?= htmlspecialchars($user->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                            <?php else: ?>
                                <?= htmlspecialchars($userInitial, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </span>
                        <span class="name"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <div class="topbar-dropdown-panel user-menu-panel" data-dropdown-panel hidden>
                        <?php if ($isAdmin): ?>
                            <a href="<?= $basePath ?>/dashboard/configuracoes" class="user-menu-item">
                                <i class="bi bi-gear"></i> Configurações
                            </a>
                        <?php elseif ($temAcessoFinanceiro): ?>
                            <a href="<?= $basePath ?>/dashboard/financeiro/plano" class="user-menu-item">
                                <i class="bi bi-star"></i> Plano contratado
                            </a>
                        <?php endif; ?>
                        <form method="POST" action="<?= $basePath ?>/logout">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="user-menu-item user-menu-item-danger">
                                <i class="bi bi-box-arrow-right"></i> Sair
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div class="dash-content">
            <?php if ($avisoTrialTexto !== '' && $planoUrl !== null): ?>
                <a href="<?= $planoUrl ?>" class="dash-pix-aviso">
                    <i class="bi bi-hourglass-split"></i>
                    <span><?= htmlspecialchars($avisoTrialTexto, ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            <?php endif; ?>

            <?php if ($avisoFaturaPix !== null): ?>
                <a href="<?= $basePath ?>/dashboard/fatura-vencida" class="dash-pix-aviso">
                    <i class="bi bi-qr-code"></i>
                    <span><?= htmlspecialchars($avisoFaturaPixTexto, ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </div>
</div>

<a
    href="https://wa.me/5511933252478"
    target="_blank"
    rel="noopener"
    class="dash-whatsapp-fab"
    aria-label="Suporte KADOSYS pelo WhatsApp"
    title="Suporte KADOSYS pelo WhatsApp"
>
    <i class="bi bi-whatsapp"></i>
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $basePath ?>/assets/js/kadosys-modal.js?v=<?= View::assetVersion('assets/js/kadosys-modal.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/dashboard.js?v=<?= View::assetVersion('assets/js/dashboard.js') ?>"></script>
</body>
</html>
