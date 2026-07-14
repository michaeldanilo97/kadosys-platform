<?php

use Igrejas\Core\View;
use Igrejas\Models\User;

/**
 * Perfil de uma pessoa da equipe - aberto ao clicar no nome/foto na
 * galeria (ver EquipeController::index). Somente leitura: edicao de
 * cargo/instrumento/foto continua em "Meu perfil" (auto-atendimento,
 * PerfilController) e edicao de dados/senha/permissoes continua nos
 * modulos Usuarios/Permissoes, restritos a admin - aqui so aparecem
 * links pra la quando quem esta vendo e admin.
 *
 * @var array $config
 * @var User $pessoa
 * @var bool $ehAdmin
 * @var string|null $logoIgreja
 * @var array<int, array{title:string, icon:string, nivel:string}> $acessoModulos
 */
$basePath = $config['base_path'] ?? '';
$cargoInfo = User::CARGOS[$pessoa->cargo] ?? User::CARGOS[User::CARGO_MEMBRO];
$temLouvores = $pessoa->musico || $pessoa->liderLouvor;
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/equipe.css?v=<?= View::assetVersion('assets/css/equipe.css') ?>">

<div class="dash-page-head">
    <div>
        <nav class="member-breadcrumb-back">
            <a href="<?= $basePath ?>/dashboard/equipe"><i class="bi bi-arrow-left"></i> Equipe</a>
        </nav>
    </div>
</div>

<div class="member-profile-header dash-panel">
    <div class="member-profile-avatar">
        <?php if ($pessoa->fotoPath !== null): ?>
            <img src="<?= $basePath ?>/<?= htmlspecialchars($pessoa->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($pessoa->name, ENT_QUOTES, 'UTF-8') ?>">
        <?php else: ?>
            <?= htmlspecialchars(mb_strtoupper(mb_substr($pessoa->name, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </div>
    <div class="member-profile-heading">
        <div class="member-profile-name-row">
            <h1><?= htmlspecialchars($pessoa->name, ENT_QUOTES, 'UTF-8') ?></h1>
            <span class="status-badge is-ativo">Ativo</span>
        </div>
        <div class="member-profile-meta">
            <span>
                <i class="bi <?= $pessoa->role === User::ROLE_ADMIN ? 'bi-shield-check' : 'bi-person-badge' ?>"></i>
                <?= $pessoa->role === User::ROLE_ADMIN ? 'Administrador' : 'Usuário' ?>
            </span>
            <?php if ($pessoa->createdAt): ?>
                <span><i class="bi bi-calendar-check"></i> Na equipe desde <?= (new DateTimeImmutable($pessoa->createdAt))->format('d/m/Y') ?></span>
            <?php endif; ?>
            <span><i class="bi bi-hash"></i> ID <?= str_pad((string) $pessoa->id, 4, '0', STR_PAD_LEFT) ?></span>
        </div>
        <div class="member-profile-badges">
            <span class="member-badge">
                <i class="bi <?= htmlspecialchars($cargoInfo['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                <?= htmlspecialchars($cargoInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                <?php if ($pessoa->cargo === User::CARGO_MUSICO && $pessoa->instrumento !== null && isset(User::INSTRUMENTOS[$pessoa->instrumento])): ?>
                    &middot; <?= htmlspecialchars(User::INSTRUMENTOS[$pessoa->instrumento]['label'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </span>
        </div>
    </div>
    <div class="member-profile-contact">
        <a href="mailto:<?= htmlspecialchars($pessoa->email, ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-envelope"></i> <?= htmlspecialchars($pessoa->email, ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>
</div>

<div class="member-tabs-body <?= $ehAdmin ? '' : 'is-single-col' ?>">
    <div class="member-tabs-main">
        <div class="dash-panel member-tab-panel is-active">
            <h3><i class="bi bi-shield-lock"></i> Acesso ao sistema</h3>

            <?php if ($pessoa->role === User::ROLE_ADMIN): ?>
                <p class="member-tab-empty">
                    Como administrador, tem acesso completo a todos os módulos do sistema,
                    incluindo Usuários, Permissões e Configurações.
                </p>
            <?php elseif ($acessoModulos === []): ?>
                <p class="member-tab-empty">Nenhum módulo liberado para este usuário no momento.</p>
            <?php else: ?>
                <ul class="member-vinculo-list">
                    <?php foreach ($acessoModulos as $modulo): ?>
                        <li>
                            <div>
                                <i class="bi <?= htmlspecialchars($modulo['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                <strong><?= htmlspecialchars($modulo['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <span class="member-badge-papel <?= $modulo['nivel'] === User::NIVEL_EDITAR ? 'is-lider' : '' ?>">
                                <?= $modulo['nivel'] === User::NIVEL_EDITAR ? 'Editar' : 'Visualizar' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($temLouvores): ?>
                        <li>
                            <div>
                                <i class="bi bi-music-note-list"></i>
                                <strong>Louvores</strong>
                                <span class="member-tab-dim">liberado por ser músico/líder de louvor</span>
                            </div>
                            <span class="member-badge-papel is-lider">Editar</span>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($ehAdmin): ?>
        <aside class="member-tabs-side">
            <div class="dash-panel member-side-card">
                <div class="member-side-card-head">
                    <h4><i class="bi bi-gear"></i> Gerenciar</h4>
                </div>
                <div class="equipe-perfil-acoes">
                    <a href="<?= $basePath ?>/dashboard/usuarios/<?= $pessoa->id ?>/editar" class="btn-k btn-k-outline">
                        <i class="bi bi-pencil"></i> Editar usuário
                    </a>
                    <?php if ($pessoa->role === User::ROLE_USUARIO): ?>
                        <a href="<?= $basePath ?>/dashboard/permissoes/<?= $pessoa->id ?>/editar" class="btn-k btn-k-outline">
                            <i class="bi bi-shield-lock"></i> Editar permissões
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    <?php endif; ?>
</div>
