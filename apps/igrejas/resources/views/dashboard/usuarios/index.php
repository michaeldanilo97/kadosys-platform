<?php

use Igrejas\Models\User;

/**
 * @var array $config
 * @var array<int, User> $usuarios
 * @var int|null $usuarioAtualId
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';

$roleLabels = [User::ROLE_ADMIN => 'Administrador', User::ROLE_USUARIO => 'Usuário'];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Usuários</h1>
        <p class="dash-page-subtitle">
            <?= count($usuarios) ?> usuário<?= count($usuarios) === 1 ? '' : 's' ?> com acesso ao sistema.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/usuarios/novo" class="btn-k btn-k-grad">
            <i class="bi bi-person-plus"></i> Novo usuário
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <div class="crud-table-wrapper">
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>E-mail</th>
                    <th>Papel</th>
                    <th>Status</th>
                    <th class="actions-col">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td>
                            <div class="crud-person">
                                <span class="crud-avatar">
                                    <?= htmlspecialchars(mb_strtoupper(mb_substr($usuario->name, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span>
                                    <?= htmlspecialchars($usuario->name, ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($usuario->id === $usuarioAtualId): ?>
                                        <span class="crud-text-dim">(você)</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($usuario->email, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= $roleLabels[$usuario->role] ?? $usuario->role ?>
                            <?php if ($usuario->musico): ?>
                                <span class="status-badge is-ativo" style="margin-left: 0.35rem;"><i class="bi bi-music-note-beamed"></i> Músico</span>
                            <?php endif; ?>
                            <?php if ($usuario->liderLouvor): ?>
                                <span class="status-badge is-ativo" style="margin-left: 0.35rem;"><i class="bi bi-star-fill"></i> Líder de louvor</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?= $usuario->active ? 'is-ativo' : 'is-inativo' ?>">
                                <?= $usuario->active ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td class="actions-col">
                            <a
                                href="<?= $basePath ?>/dashboard/usuarios/<?= $usuario->id ?>/editar"
                                class="crud-icon-btn"
                                aria-label="Editar <?= htmlspecialchars($usuario->name, ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($usuario->id !== $usuarioAtualId): ?>
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/dashboard/usuarios/<?= $usuario->id ?>/excluir"
                                    data-confirm="Remover o usuário &quot;<?= htmlspecialchars($usuario->name, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button
                                        type="submit"
                                        class="crud-icon-btn danger"
                                        aria-label="Excluir <?= htmlspecialchars($usuario->name, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
