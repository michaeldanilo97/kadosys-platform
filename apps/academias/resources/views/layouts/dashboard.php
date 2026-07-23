<?php

use Academias\Core\Csrf;
use Academias\Core\View;
use Academias\Models\AcademiaAviso;

/**
 * @var string $content
 * @var string $pageTitle
 * @var array $config
 * @var \Academias\Models\Academia|null $academia
 * @var \Academias\Models\User|null $user
 * @var string|null $activeMenu
 */
$basePath = $config['base_path'] ?? '';
$menu = $activeMenu ?? '';
$tituloTopbar = preg_replace('/\s*-\s*KADOSYS Academias$/', '', $pageTitle ?? '') ?: 'Painel';
$iniciais = $user?->name ? mb_strtoupper(mb_substr($user->name, 0, 1, 'UTF-8'), 'UTF-8') : 'U';

// Aviso da plataforma (publicado pelo Super Admin) - o mesmo sino em
// toda pagina do painel, independente do controller que a renderizou,
// mesmo padrao ja usado em Barbearias\Models\BarbeariaAviso. A tabela
// ja existe desde a Fase 1, mas so passa a receber avisos de fato
// quando o Super Admin integrar o Academias (ver Fase 7).
$avisoPlataforma = AcademiaAviso::ativo();

// Menu cresce fase a fase - os itens so entram aqui junto com o
// controller correspondente, pra nunca ter um link que ainda nao
// existe (Ficha de Treino, Avaliação Física e Relatórios chegam nas
// proximas fases).
$itensMenu = [
    ['slug' => 'painel', 'href' => '/dashboard', 'icone' => '🏠', 'label' => 'Painel'],
    ['slug' => 'checkin', 'href' => '/dashboard/checkin', 'icone' => '📲', 'label' => 'Check-in'],
    ['slug' => 'ranking', 'href' => '/dashboard/ranking', 'icone' => '🏆', 'label' => 'Ranking'],
    ['slug' => 'alunos', 'href' => '/dashboard/alunos', 'icone' => '🏋️', 'label' => 'Alunos'],
    ['slug' => 'professores', 'href' => '/dashboard/professores', 'icone' => '🧑‍🏫', 'label' => 'Professores'],
    ['slug' => 'planos-matricula', 'href' => '/dashboard/planos-matricula', 'icone' => '📦', 'label' => 'Planos de Matrícula'],
    ['slug' => 'financeiro', 'href' => '/dashboard/financeiro', 'icone' => '💰', 'label' => 'Financeiro'],
    ['slug' => 'faturas', 'href' => '/dashboard/faturas', 'icone' => '🧾', 'label' => 'Faturas'],
    ['slug' => 'configuracoes', 'href' => '/dashboard/configuracoes', 'icone' => '⚙️', 'label' => 'Configurações'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'KADOSYS Academias', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="manifest" href="<?= $basePath ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?= $basePath ?>/assets/icons/apple-touch-icon.png">
    <meta name="theme-color" content="#0F172A">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css?v=<?= View::assetVersion('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/site.css?v=<?= View::assetVersion('assets/css/site.css') ?>">
    <?php if ($academia?->corPrimaria !== null): ?>
        <style>:root { --primary: <?= htmlspecialchars($academia->corPrimaria, ENT_QUOTES, 'UTF-8') ?>; }</style>
    <?php endif; ?>
    <script>
        // Aplica o tema ANTES da primeira renderizacao, pra nao piscar
        // escuro por uma fracao de segundo quando o padrao e o claro.
        // O tema padrao do painel e o claro - so troca se a pessoa ja
        // escolheu "escuro" antes (ver assets/js/dashboard.js).
        (function () {
            try {
                if (window.localStorage.getItem('kadosys_academias_theme') !== 'dark') {
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
            <?php if ($academia?->logoPath): ?>
                <img src="<?= $basePath . '/' . htmlspecialchars($academia->logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($academia->nome, ENT_QUOTES, 'UTF-8') ?>" class="dash-sidebar-logo">
            <?php else: ?>
                <span class="dash-sidebar-brand-label"><span class="text-gradient">KADOSYS</span> Academias</span>
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
            <p class="academia-nome"><?= htmlspecialchars($academia->nome ?? '', ENT_QUOTES, 'UTF-8') ?></p>
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
                    <div class="topbar-dropdown-head"><?= htmlspecialchars($academia->nome ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <a href="<?= $basePath ?>/dashboard/configuracoes" class="user-panel-item">⚙️ Configurações</a>
                    <form method="POST" action="<?= $basePath ?>/logout" class="user-panel-item">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-logout-inline">🚪 Sair</button>
                    </form>
                </div>
            </div>
        </header>

        <?= $content ?>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/dashboard.js?v=<?= View::assetVersion('assets/js/dashboard.js') ?>"></script>
</body>
</html>
