<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\FilaAtendimento;
use Barbearias\Models\Profissional;

/**
 * @var array $config
 * @var \Barbearias\Models\Barbearia $barbearia
 * @var array<int, FilaAtendimento> $fila
 * @var array<int, Profissional> $profissionais
 * @var string|null $success
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';

$aguardando = array_values(array_filter($fila, static fn (FilaAtendimento $item) => $item->status === FilaAtendimento::STATUS_AGUARDANDO));
$emAtendimento = array_values(array_filter($fila, static fn (FilaAtendimento $item) => $item->status === FilaAtendimento::STATUS_EM_ATENDIMENTO));
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Atendimento</p>
            <h1 class="dashboard-title">Fila</h1>
            <p class="dash-page-subtitle">
                <?= count($aguardando) ?> aguardando, <?= count($emAtendimento) ?> em atendimento agora.
            </p>
        </div>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Link público da fila</h2>
            <p>Compartilhe com seus clientes - eles entram na fila sozinhos, sem precisar estar no salão.</p>
        </div>
        <div class="pix-copiacola" data-link-fila data-caminho="<?= $basePath ?>/fila/<?= htmlspecialchars($barbearia->slug, ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" readonly data-link-fila-input>
            <button type="button" class="btn-k btn-k-grad btn-k-sm" data-link-fila-copiar>Copiar</button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success"><div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div></div>
    <?php endif; ?>
    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Adicionar cliente</h2>
        </div>
        <form method="POST" action="<?= $basePath ?>/dashboard/fila" class="crud-form-grid">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required autofocus>
            </div>
            <div class="form-field">
                <label for="telefone">Telefone (opcional)</label>
                <input type="text" id="telefone" name="telefone" placeholder="(11) 90000-0000">
            </div>
            <?php if ($profissionais !== []): ?>
                <div class="form-field">
                    <label for="profissional_id">Preferência de profissional (opcional)</label>
                    <select id="profissional_id" name="profissional_id">
                        <option value="0">Qualquer um</option>
                        <?php foreach ($profissionais as $profissional): ?>
                            <option value="<?= $profissional->id ?>"><?= htmlspecialchars($profissional->nome, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="crud-form-actions" style="align-self:end;">
                <button type="submit" class="btn-k btn-k-grad">Entrar na fila</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <div class="dash-panel-head">
            <h2>Fila agora</h2>
        </div>
        <?php if ($fila === []): ?>
            <p class="crud-empty">Ninguém na fila no momento.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Contato</th>
                            <th>Profissional</th>
                            <th>Entrou em</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fila as $indice => $item): ?>
                            <tr>
                                <td class="text-dim"><?= $indice + 1 ?></td>
                                <td><?= htmlspecialchars($item->nome, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $item->telefone ? htmlspecialchars($item->telefone, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                <td class="text-dim">
                                    <?php
                                    $prof = null;
                                    foreach ($profissionais as $p) {
                                        if ($p->id === $item->profissionalId) {
                                            $prof = $p;
                                            break;
                                        }
                                    }
                                    ?>
                                    <?= $prof !== null ? htmlspecialchars($prof->nome, ENT_QUOTES, 'UTF-8') : 'Qualquer um' ?>
                                </td>
                                <td class="text-dim"><?= (new DateTimeImmutable($item->entrouEm))->format('H:i') ?></td>
                                <td>
                                    <?= $item->status === FilaAtendimento::STATUS_EM_ATENDIMENTO
                                        ? '<span class="status-badge ok">Em atendimento</span>'
                                        : '<span class="status-badge dim">Aguardando</span>' ?>
                                </td>
                                <td class="actions-col">
                                    <?php if ($item->status === FilaAtendimento::STATUS_AGUARDANDO): ?>
                                        <form method="POST" action="<?= $basePath ?>/dashboard/fila/<?= $item->id ?>/chamar">
                                            <?= Csrf::field() ?>
                                            <button type="submit" class="crud-icon-btn" title="Chamar">📣</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?= $basePath ?>/dashboard/fila/<?= $item->id ?>/concluir" class="crud-icon-btn" title="Concluir e registrar pagamento">✅</a>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/fila/<?= $item->id ?>/cancelar" onsubmit="return confirm('Remover da fila?');">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Remover">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    (function () {
        var bloco = document.querySelector('[data-link-fila]');
        if (!bloco) {
            return;
        }

        var input = bloco.querySelector('[data-link-fila-input]');
        var botao = bloco.querySelector('[data-link-fila-copiar]');
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
