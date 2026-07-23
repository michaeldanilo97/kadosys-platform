<?php

use Barbearias\Core\Csrf;
use Barbearias\Core\View;
use Barbearias\Models\BarbeariaAviso;

/**
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 * @var \Barbearias\Models\Barbearia|null $barbearia
 * @var \Barbearias\Models\User|null $user
 * @var string|null $activeMenu
 */
$basePath = $config['base_path'] ?? '';
$menu = $activeMenu ?? '';
$tituloTopbar = preg_replace('/\s*-\s*KADOSYS Barbearias$/', '', $pageTitle ?? '') ?: 'Painel';
$iniciais = $user?->name ? mb_strtoupper(mb_substr($user->name, 0, 1, 'UTF-8'), 'UTF-8') : 'U';

// Aviso da plataforma (publicado pelo Super Admin) - o mesmo sino em
// toda pagina do painel, independente do controller que a renderizou,
// por isso calculado aqui direto no layout (ver Igrejas\Models\PlataformaAviso
// pro mesmo padrao no outro app).
$avisoPlataforma = BarbeariaAviso::ativo();

// Aviso de teste gratis (contagem regressiva) - so aparece pra quem
// ainda esta em trial e nao vencido (depois de vencer, o
// AuthMiddleware ja bloqueia o resto do painel e manda pra
// /dashboard/assinatura - por isso nao repete o aviso nessa mesma
// tela). Mesmo padrao ja usado no Igrejas.
$avisoTrialTexto = '';
if ($menu !== 'assinatura' && $barbearia?->metodoPagamento === 'trial' && $barbearia->trialExpiraEm !== null) {
    $expiraEm = new \DateTimeImmutable($barbearia->trialExpiraEm);
    $agora = new \DateTimeImmutable();

    if ($agora <= $expiraEm) {
        // Diferenca por DATA de calendario (sem a hora do dia) - com
        // timestamp bruto + ceil(), 1 dia e poucas horas de sobra
        // arredondava pra "2 dias", mesmo a data de vencimento sendo
        // literalmente amanha.
        $diasRestantes = (int) $agora->setTime(0, 0, 0)->diff($expiraEm->setTime(0, 0, 0))->days;
        $avisoTrialTexto = sprintf(
            'Seu teste grátis termina em %d dia%s (%s). Clique aqui para escolher um plano.',
            $diasRestantes,
            $diasRestantes === 1 ? '' : 's',
            $expiraEm->format('d/m/Y')
        );
    }
}

$itensMenu = [
    ['slug' => 'painel', 'href' => '/dashboard', 'icone' => '🏠', 'label' => 'Painel'],
];

// Fila e Agendamento sao modos alternativos (ver Barbearia::usaFila()) -
// a barbearia usa um ou outro, nunca os dois ao mesmo tempo.
if ($barbearia?->usaFila()) {
    $itensMenu[] = ['slug' => 'fila', 'href' => '/dashboard/fila', 'icone' => '🎟️', 'label' => 'Fila'];
} else {
    $itensMenu[] = ['slug' => 'agendamentos', 'href' => '/dashboard/agendamentos', 'icone' => '📅', 'label' => 'Agendamentos'];
    $itensMenu[] = ['slug' => 'bloqueios', 'href' => '/dashboard/bloqueios', 'icone' => '🚫', 'label' => 'Bloqueios'];
    $itensMenu[] = ['slug' => 'lista-espera', 'href' => '/dashboard/lista-espera', 'icone' => '⏳', 'label' => 'Lista de espera'];
}

array_push(
    $itensMenu,
    ['slug' => 'recepcao', 'href' => '/dashboard/recepcao', 'icone' => '📺', 'label' => 'Recepção (TV)', 'target' => '_blank'],
    ['slug' => 'clientes', 'href' => '/dashboard/clientes', 'icone' => '📇', 'label' => 'Clientes'],
    ['slug' => 'crm', 'href' => '/dashboard/crm', 'icone' => '🎯', 'label' => 'CRM'],
    ['slug' => 'fidelidade', 'href' => '/dashboard/fidelidade', 'icone' => '🏆', 'label' => 'Fidelidade'],
    ['slug' => 'assinaturas-clientes', 'href' => '/dashboard/assinaturas-clientes', 'icone' => '📦', 'label' => 'Assinaturas'],
    ['slug' => 'profissionais', 'href' => '/dashboard/profissionais', 'icone' => '👤', 'label' => 'Profissionais'],
    ['slug' => 'servicos', 'href' => '/dashboard/servicos', 'icone' => '✂️', 'label' => 'Serviços'],
    ['slug' => 'produtos', 'href' => '/dashboard/produtos', 'icone' => '🧴', 'label' => 'Produtos'],
    ['slug' => 'unidades', 'href' => '/dashboard/unidades', 'icone' => '🏢', 'label' => 'Unidades'],
    ['slug' => 'financeiro', 'href' => '/dashboard/financeiro', 'icone' => '💰', 'label' => 'Financeiro'],
    ['slug' => 'comissoes', 'href' => '/dashboard/comissoes', 'icone' => '💸', 'label' => 'Comissões'],
    ['slug' => 'relatorios', 'href' => '/dashboard/relatorios', 'icone' => '📊', 'label' => 'Relatórios'],
    ['slug' => 'faturas', 'href' => '/dashboard/faturas', 'icone' => '🧾', 'label' => 'Faturas'],
    ['slug' => 'configuracoes', 'href' => '/dashboard/configuracoes', 'icone' => '⚙️', 'label' => 'Configurações'],
);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'KADOSYS Barbearias', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="manifest" href="<?= $basePath ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= $basePath ?>/assets/icons/apple-touch-icon.png">
    <meta name="theme-color" content="#0F172A">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/site.css?v=<?= View::assetVersion('assets/css/site.css') ?>">
    <?php if ($barbearia?->corPrimaria !== null): ?>
        <style>:root { --primary: <?= htmlspecialchars($barbearia->corPrimaria, ENT_QUOTES, 'UTF-8') ?>; }</style>
    <?php endif; ?>
    <script>
        // Aplica o tema ANTES da primeira renderizacao, pra nao piscar
        // escuro por uma fracao de segundo quando o padrao e o claro.
        // O tema padrao do painel e o claro - so troca se a pessoa ja
        // escolheu "escuro" antes (ver assets/js/dashboard.js).
        (function () {
            try {
                if (window.localStorage.getItem('kadosys_barbearias_theme') !== 'dark') {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            } catch (error) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body data-base-path="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>">
<div class="dash-shell">
    <div class="sidebar-overlay" data-sidebar-overlay></div>
    <aside class="dash-sidebar" data-sidebar>
        <div class="dash-sidebar-brand">
            <?php if ($barbearia?->logoPath): ?>
                <img src="<?= $basePath . '/' . htmlspecialchars($barbearia->logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') ?>" class="dash-sidebar-logo">
            <?php else: ?>
                <span class="dash-sidebar-brand-label"><span class="text-gradient">KADOSYS</span> Barbearias</span>
            <?php endif; ?>
            <button type="button" class="dash-sidebar-collapse-btn" data-sidebar-collapse aria-label="Recolher menu">
                <span>«</span>
            </button>
        </div>
        <nav class="dash-nav">
            <?php foreach ($itensMenu as $item): ?>
                <a href="<?= $basePath . $item['href'] ?>" class="dash-nav-link<?= $menu === $item['slug'] ? ' active' : '' ?>" title="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>" <?= isset($item['target']) ? 'target="' . $item['target'] . '"' : '' ?>>
                    <span class="icone"><?= $item['icone'] ?></span>
                    <span class="dash-nav-link-label"><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="dash-sidebar-footer">
            <p class="barbearia-nome"><?= htmlspecialchars($barbearia->nome ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="usuario-nome"><?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="dash-sidebar-footer-actions">
                <div class="topbar-dropdown topbar-dropdown-up" data-topbar-dropdown>
                    <button type="button" class="btn-theme-toggle" aria-label="Notificações" aria-expanded="false" data-dropdown-toggle>
                        🔔 <span class="dash-sidebar-footer-label">Avisos</span>
                        <?php if ($avisoPlataforma !== null): ?><span class="dot"></span><?php endif; ?>
                    </button>

                    <div class="topbar-dropdown-panel notif-panel" data-dropdown-panel hidden>
                        <div class="topbar-dropdown-head">Avisos da plataforma</div>

                        <?php if ($avisoPlataforma === null): ?>
                            <div class="notif-empty">Nenhum aviso no momento.</div>
                        <?php else: ?>
                            <div class="notif-item">
                                <span>
                                    <?= htmlspecialchars($avisoPlataforma->mensagem, ENT_QUOTES, 'UTF-8') ?>
                                    <span class="notif-data"><?= (new DateTimeImmutable($avisoPlataforma->createdAt))->format('d/m/Y') ?></span>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="button" class="btn-theme-toggle" data-theme-toggle aria-label="Alternar tema claro/escuro">
                    <span data-theme-icon>🌙</span> <span class="dash-sidebar-footer-label">Tema</span>
                </button>
            </div>
        </div>
    </aside>

    <div class="dash-main">
        <header class="dash-topbar">
            <button type="button" class="sidebar-toggle-btn" data-sidebar-toggle aria-label="Abrir menu">☰</button>
            <h1 class="dash-topbar-title"><?= htmlspecialchars($tituloTopbar, ENT_QUOTES, 'UTF-8') ?></h1>

            <div class="topbar-dropdown dash-topbar-user" data-topbar-dropdown>
                <button type="button" class="dash-topbar-user-btn" aria-label="Menu do usuário" aria-expanded="false" data-dropdown-toggle>
                    <span class="dash-topbar-avatar"><?= htmlspecialchars($iniciais, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="dash-topbar-user-name"><?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="dash-topbar-user-chevron">⌄</span>
                </button>

                <div class="topbar-dropdown-panel user-panel" data-dropdown-panel hidden>
                    <div class="topbar-dropdown-head"><?= htmlspecialchars($barbearia->nome ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <a href="<?= $basePath ?>/dashboard/configuracoes" class="user-panel-item">⚙️ Configurações</a>
                    <form method="POST" action="<?= $basePath ?>/logout" class="user-panel-item">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-logout-inline">🚪 Sair</button>
                    </form>
                </div>
            </div>
        </header>

        <?php if ($avisoTrialTexto !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/assinatura" class="dash-pix-aviso">
                <span>⏳</span>
                <span><?= htmlspecialchars($avisoTrialTexto, ENT_QUOTES, 'UTF-8') ?></span>
                <span>→</span>
            </a>
        <?php endif; ?>

        <?= $content ?>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/dashboard.js?v=<?= View::assetVersion('assets/js/dashboard.js') ?>"></script>
</body>
</html>
