<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Barbearia;
use Barbearias\Models\User;

/**
 * @var array $config
 * @var Barbearia $barbearia
 * @var array<int, User> $equipe
 * @var string $planoLabel
 * @var array<int, string> $perfilErrors
 * @var string|null $perfilSuccess
 * @var array<int, string> $equipeErrors
 * @var array $equipeOld
 * @var string|null $equipeSuccess
 * @var User $user
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Conta</p>
            <h1 class="dashboard-title">Configurações</h1>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Plano e cobrança</h2>
        </div>
        <p style="color: var(--gray-400); margin: 0 0 1rem;">
            Plano atual: <strong style="color: var(--text);"><?= htmlspecialchars($planoLabel, ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
        <div class="crud-form-actions" style="margin-top: 0;">
            <a href="<?= $basePath ?>/dashboard/faturas" class="btn-k btn-k-outline">Ver faturas</a>
            <a href="<?= $basePath ?>/dashboard/assinatura" class="btn-k btn-k-outline">Gerenciar pagamento</a>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Dados da barbearia</h2>
        </div>

        <?php if ($perfilSuccess): ?>
            <div class="form-alert form-alert-success">
                <div><?= htmlspecialchars($perfilSuccess, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <?php if ($perfilErrors !== []): ?>
            <div class="form-alert">
                <?php foreach ($perfilErrors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/perfil">
            <?= Csrf::field() ?>
            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="nome">Nome da barbearia</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($barbearia->nome, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($barbearia->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
            </div>
            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad">Salvar</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Agendamento online</h2>
            <p>Compartilhe esse link com seus clientes - eles escolhem o profissional, o serviço e o horário sozinhos.</p>
        </div>
        <div class="pix-copiacola" data-link-agendamento data-caminho="<?= $basePath ?>/agendar/<?= htmlspecialchars($barbearia->slug, ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" readonly data-link-agendamento-input>
            <button type="button" class="btn-k btn-k-grad btn-k-sm" data-link-agendamento-copiar>Copiar</button>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Equipe</h2>
            <p>Quem tem acesso ao painel da barbearia.</p>
        </div>

        <?php if ($equipeSuccess): ?>
            <div class="form-alert form-alert-success">
                <div><?= htmlspecialchars($equipeSuccess, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <?php if ($equipeErrors !== []): ?>
            <div class="form-alert">
                <?php foreach ($equipeErrors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Papel</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipe as $membro): ?>
                        <tr>
                            <td><?= htmlspecialchars($membro->name, ENT_QUOTES, 'UTF-8') ?><?= $membro->id === $user->id ? ' <span class="text-dim">(você)</span>' : '' ?></td>
                            <td class="text-dim"><?= htmlspecialchars($membro->email, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="status-badge info"><?= $membro->role === User::ROLE_ADMIN ? 'Admin' : 'Equipe' ?></span></td>
                            <td><span class="status-badge <?= $membro->active ? 'ok' : 'dim' ?>"><?= $membro->active ? 'Ativo' : 'Inativo' ?></span></td>
                            <td class="actions-col">
                                <a href="<?= $basePath ?>/dashboard/configuracoes/equipe/<?= $membro->id ?>/editar" class="crud-icon-btn" title="Editar">✏️</a>
                                <?php if ($membro->id !== $user->id): ?>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/equipe/<?= $membro->id ?>/excluir" onsubmit="return confirm('Remover o acesso de <?= htmlspecialchars(addslashes($membro->name), ENT_QUOTES, 'UTF-8') ?>?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Remover">🗑️</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 style="font-size: 0.95rem; margin: 1.75rem 0 1rem;">Adicionar acesso</h3>
        <form method="POST" action="<?= $basePath ?>/dashboard/configuracoes/equipe">
            <?= Csrf::field() ?>
            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="eq_name">Nome</label>
                    <input type="text" id="eq_name" name="name" value="<?= htmlspecialchars($equipeOld['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="eq_email">E-mail</label>
                    <input type="email" id="eq_email" name="email" value="<?= htmlspecialchars($equipeOld['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="eq_password">Senha</label>
                    <input type="password" id="eq_password" name="password" minlength="8" placeholder="Mínimo 8 caracteres" required>
                </div>
                <div class="form-field">
                    <label for="eq_role">Papel</label>
                    <select id="eq_role" name="role">
                        <option value="usuario" <?= ($equipeOld['role'] ?? 'usuario') === 'usuario' ? 'selected' : '' ?>>Equipe</option>
                        <option value="admin" <?= ($equipeOld['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
            </div>
            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad">Adicionar</button>
            </div>
        </form>
    </div>
</main>

<script>
    (function () {
        var bloco = document.querySelector('[data-link-agendamento]');
        if (!bloco) {
            return;
        }

        var input = bloco.querySelector('[data-link-agendamento-input]');
        var botao = bloco.querySelector('[data-link-agendamento-copiar]');
        var link = window.location.origin + bloco.getAttribute('data-caminho');

        input.value = link;

        botao.addEventListener('click', function () {
            input.select();
            navigator.clipboard.writeText(link).then(function () {
                botao.textContent = 'Copiado!';
                setTimeout(function () { botao.textContent = 'Copiar'; }, 2000);
            });
        });
    })();
</script>
