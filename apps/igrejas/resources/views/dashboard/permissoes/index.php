<?php

/**
 * @var array $config
 * @var array<int, \Igrejas\Models\User> $usuarios
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Permissões</h1>
        <p class="dash-page-subtitle">
            Restrinja quais módulos cada usuário pode acessar. Administradores sempre têm acesso total.
        </p>
    </div>
</div>

<div class="dash-panel">
    <?php if ($usuarios === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-shield-lock"></i></div>
            <h2>Nenhum usuário com papel "Usuário" ainda</h2>
            <p>
                Permissões só se aplicam a usuários com papel "Usuário" - administradores sempre veem tudo.
                Cadastre ou edite um usuário em <a href="<?= $basePath ?>/dashboard/usuarios">Usuários</a>.
            </p>
        </div>
    <?php else: ?>
        <ul class="crud-people-list">
            <?php foreach ($usuarios as $usuario): ?>
                <li>
                    <div class="crud-person">
                        <span class="crud-avatar">
                            <?php if ($usuario->fotoPath !== null): ?>
                                <img src="<?= $basePath ?>/<?= htmlspecialchars($usuario->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                            <?php else: ?>
                                <?= htmlspecialchars(mb_strtoupper(mb_substr($usuario->name, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </span>
                        <span>
                            <?= htmlspecialchars($usuario->name, ENT_QUOTES, 'UTF-8') ?>
                            <div class="crud-text-dim" style="font-size: 0.78rem;"><?= htmlspecialchars($usuario->email, ENT_QUOTES, 'UTF-8') ?></div>
                        </span>
                    </div>
                    <a href="<?= $basePath ?>/dashboard/permissoes/<?= $usuario->id ?>/editar" class="btn-k btn-k-ghost">
                        <i class="bi bi-sliders"></i> Editar permissões
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
