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

// Aviso da plataforma (publicado pelo Super Admin) - o mesmo sino em
// toda pagina do painel, independente do controller que a renderizou,
// por isso calculado aqui direto no layout (ver Igrejas\Models\PlataformaAviso
// pro mesmo padrao no outro app).
$avisoPlataforma = BarbeariaAviso::ativo();

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
                <span class="text-gradient">KADOSYS</span> Barbearias
            <?php endif; ?>
        </div>
        <nav class="dash-nav">
            <?php foreach ($itensMenu as $item): ?>
                <a href="<?= $basePath . $item['href'] ?>" class="dash-nav-link<?= $menu === $item['slug'] ? ' active' : '' ?>" <?= isset($item['target']) ? 'target="' . $item['target'] . '"' : '' ?>>
                    <span class="icone"><?= $item['icone'] ?></span>
                    <span><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="dash-sidebar-footer">
            <p class="barbearia-nome"><?= htmlspecialchars($barbearia->nome ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="usuario-nome"><?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="dash-sidebar-footer-actions">
                <div class="topbar-dropdown topbar-dropdown-up" data-topbar-dropdown>
                    <button type="button" class="btn-theme-toggle" aria-label="Notificações" aria-expanded="false" data-dropdown-toggle>
                        🔔 Avisos
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
                    <span data-theme-icon>🌙</span> Tema
                </button>
                <form method="POST" action="<?= $basePath ?>/logout">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn-logout">Sair</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="dash-main">
        <header class="dash-topbar">
            <button type="button" class="sidebar-toggle-btn" data-sidebar-toggle aria-label="Abrir menu">☰</button>
            <span class="brand"><span class="text-gradient">KADOSYS</span></span>
        </header>

        <?= $content ?>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/dashboard.js?v=<?= View::assetVersion('assets/js/dashboard.js') ?>"></script>
</body>
</html>
