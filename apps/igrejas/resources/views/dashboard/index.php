<?php

use Igrejas\Core\TenantResolver;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Plano;
use Igrejas\Models\User;

/**
 * @var array $config
 * @var \Igrejas\Models\User|null $user
 * @var array $modules
 * @var int $membrosAtivos
 * @var int $novosMembros
 * @var int $ministeriosAtivos
 * @var \Igrejas\Models\Culto|null $proximoCulto
 * @var bool $financeiroDisponivel
 * @var array{entradas: float, saidas: float, saldo: float}|null $financeiroTotais
 * @var \Igrejas\Models\PlataformaAviso|null $avisoPlataforma
 */
$basePath = $config['base_path'] ?? '';
$firstName = explode(' ', trim($user?->name ?? 'Usuário'))[0];
$planoAtual = ConfiguracaoIgreja::atual()->plano;
$emTrial = TenantResolver::atual()?->metodoPagamento === 'trial';
$isAdmin = $user?->role === User::ROLE_ADMIN;
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Olá, <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?> <span class="wave">&#128075;</span></h1>
        <p class="dash-page-subtitle">Aqui está a visão geral da sua igreja em tempo real.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/relatorios" class="btn-k btn-k-ghost"><i class="bi bi-bar-chart-line"></i> Relatórios</a>
        <a href="<?= $basePath ?>/dashboard/membros/novo" class="btn-k btn-k-grad"><i class="bi bi-plus-lg"></i> Novo membro</a>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon blue"><i class="bi bi-people"></i></div>
            <?php if ($novosMembros > 0): ?>
                <span class="kpi-trend up"><i class="bi bi-arrow-up-short"></i> +<?= $novosMembros ?> este mês</span>
            <?php else: ?>
                <span class="kpi-trend neutral"><i class="bi bi-dash"></i> sem novidades</span>
            <?php endif; ?>
        </div>
        <div class="value"><?= $membrosAtivos ?></div>
        <div class="label">Membros ativos</div>
        <div class="delta"><a href="<?= $basePath ?>/dashboard/membros">Ver todos os membros</a></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon violet"><i class="bi bi-diagram-3"></i></div>
            <span class="kpi-trend neutral"><i class="bi bi-diagram-3"></i> ativos</span>
        </div>
        <div class="value"><?= $ministeriosAtivos ?></div>
        <div class="label">Ministérios</div>
        <div class="delta"><a href="<?= $basePath ?>/dashboard/ministerios">Ver todos os ministérios</a></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon cyan"><i class="bi bi-calendar2-week"></i></div>
            <?php if ($proximoCulto): ?>
                <span class="kpi-trend neutral"><i class="bi bi-clock"></i> <?= $proximoCulto->dataHoraFormatada() ?></span>
            <?php else: ?>
                <span class="kpi-trend neutral"><i class="bi bi-dash"></i> nada agendado</span>
            <?php endif; ?>
        </div>
        <div class="value" style="<?= $proximoCulto ? 'font-size: 1.3rem;' : '' ?>">
            <?= $proximoCulto ? htmlspecialchars($proximoCulto->titulo, ENT_QUOTES, 'UTF-8') : '--' ?>
        </div>
        <div class="label">Próximo culto</div>
        <div class="delta"><a href="<?= $basePath ?>/dashboard/cultos">Ver todos os cultos</a></div>
    </div>
    <div class="kpi-card">
        <?php if ($financeiroDisponivel && $financeiroTotais !== null): ?>
            <div class="kpi-top">
                <div class="kpi-icon <?= $financeiroTotais['saldo'] >= 0 ? 'green' : 'red' ?>"><i class="bi bi-cash-coin"></i></div>
                <span class="kpi-trend neutral"><i class="bi bi-calendar3"></i> <?= (new DateTimeImmutable())->format('m/Y') ?></span>
            </div>
            <div class="value" style="font-size: 1.5rem;">R$ <?= number_format($financeiroTotais['saldo'], 2, ',', '.') ?></div>
            <div class="label">Saldo do mês (Financeiro)</div>
            <div class="delta"><a href="<?= $basePath ?>/dashboard/financeiro">Ver lançamentos</a></div>
        <?php else: ?>
            <div class="kpi-top">
                <div class="kpi-icon green"><i class="bi bi-cash-coin"></i></div>
                <span class="kpi-trend neutral"><i class="bi bi-lock"></i> plano Plus+</span>
            </div>
            <div class="value">--</div>
            <div class="label">Financeiro do mês</div>
            <div class="delta">Disponível no módulo Financeiro</div>
        <?php endif; ?>
    </div>
</div>

<div class="dash-panels-row">
    <?php if ($isAdmin): ?>
        <div class="dash-panel dash-ai-panel">
            <div class="dash-panel-head">
                <h2><i class="bi bi-newspaper"></i> Atualizações do sistema</h2>
            </div>
            <?php if ($avisoPlataforma === null): ?>
                <p class="crud-text-dim">Nenhuma atualização por enquanto.</p>
            <?php else: ?>
                <div class="ai-insight-list">
                    <div class="ai-insight">
                        <div class="glyph"><i class="bi bi-broadcast-pin"></i></div>
                        <p>
                            <?= htmlspecialchars($avisoPlataforma->mensagem, ENT_QUOTES, 'UTF-8') ?>
                            <span class="crud-text-dim" style="display: block; font-size: 0.74rem; margin-top: 0.2rem;">
                                <?= (new DateTimeImmutable($avisoPlataforma->createdAt))->format('d/m/Y') ?>
                            </span>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="dash-panel"<?= $isAdmin ? '' : ' style="grid-column: 1 / -1;"' ?>>
        <div class="dash-panel-head">
            <h2><i class="bi bi-lightning-charge"></i> Ações rápidas</h2>
        </div>
        <div class="quick-actions">
            <a href="<?= $basePath ?>/dashboard/membros/novo" class="quick-action">
                <i class="bi bi-person-plus"></i> Cadastrar membro
            </a>
            <a href="<?= $basePath ?>/dashboard/cultos/novo" class="quick-action">
                <i class="bi bi-calendar-plus"></i> Agendar culto
            </a>
            <a href="<?= $basePath ?>/dashboard/financeiro" class="quick-action">
                <i class="bi bi-wallet2"></i> Lançar oferta
            </a>
            <a href="<?= $basePath ?>/dashboard/comunicacao" class="quick-action">
                <i class="bi bi-megaphone"></i> Enviar aviso
            </a>
        </div>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-head">
        <h2><i class="bi bi-grid-1x2"></i> Módulos do sistema</h2>
    </div>
    <div class="module-grid">
        <?php foreach ($modules as $slug => $module): ?>
            <?php
            $bloqueadoPeloPlano = !Plano::disponivel($planoAtual, $slug, $emTrial);
            $bloqueadoPelaPermissao = !$bloqueadoPeloPlano && !User::podeAcessarModulo($user, $slug);
            $bloqueado = $bloqueadoPeloPlano || $bloqueadoPelaPermissao;
            ?>
            <a href="<?= $basePath ?>/dashboard/<?= $slug ?>" class="module-card<?= $bloqueado ? ' module-card-locked' : '' ?>">
                <div class="icon"><i class="bi <?= htmlspecialchars($module['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                <div>
                    <div class="name">
                        <?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($bloqueadoPeloPlano): ?>
                            <span class="plano-badge"><i class="bi bi-lock-fill"></i> <?= htmlspecialchars(Plano::label($module['planoMinimo']), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php elseif ($bloqueadoPelaPermissao): ?>
                            <span class="plano-badge"><i class="bi bi-shield-lock"></i> Sem permissão</span>
                        <?php endif; ?>
                    </div>
                    <div class="desc"><?= htmlspecialchars($module['description'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <i class="bi bi-arrow-right-short arrow"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>
