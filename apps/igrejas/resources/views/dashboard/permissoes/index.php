<?php

/**
 * @var array $config
 * @var array<int, \Igrejas\Models\User> $usuarios
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Permissoes</h1>
        <p class="dash-page-subtitle">
            Restrinja quais modulos cada usuario pode acessar. Administradores sempre tem acesso total.
        </p>
    </div>
</div>

<div class="dash-panel">
    <?php if ($usuarios === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-shield-lock"></i></div>
            <h2>Nenhum usuario com papel "Usuario" ainda</h2>
            <p>
                Permissoes so se aplicam a usuarios com papel "Usuario" - administradores sempre veem tudo.
                Cadastre ou edite um usuario em <a href="<?= $basePath ?>/dashboard/usuarios">Usuarios</a>.
            </p>
        </div>
    <?php else: ?>
        <ul class="crud-people-list">
            <?php foreach ($usuarios as $usuario): ?>
                <li>
                    <div class="crud-person">
                        <span class="crud-avatar">
                            <?= htmlspecialchars(mb_strtoupper(mb_substr($usuario->name, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span>
                            <?= htmlspecialchars($usuario->name, ENT_QUOTES, 'UTF-8') ?>
                            <div class="crud-text-dim" style="font-size: 0.78rem;"><?= htmlspecialchars($usuario->email, ENT_QUOTES, 'UTF-8') ?></div>
                        </span>
                    </div>
                    <a href="<?= $basePath ?>/dashboard/permissoes/<?= $usuario->id ?>/editar" class="btn-k btn-k-ghost">
                        <i class="bi bi-sliders"></i> Editar permissoes
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
