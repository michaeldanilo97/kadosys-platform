<?php

use Igrejas\Models\Plano;

/**
 * @var array $config
 * @var array<int, \Igrejas\Models\Tenant> $igrejas
 * @var array<int, string>|null $success
 * @var array $errors
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';

/** @var array<string, string> */
$statusLabel = [
    'provisionando' => 'Provisionando',
    'ativo' => 'Ativo',
    'erro' => 'Erro',
    'suspenso' => 'Suspenso',
];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Igrejas provisionadas</h1>
        <p class="dash-page-subtitle">
            <?= count($igrejas) ?> igreja<?= count($igrejas) === 1 ? '' : 's' ?> no total.
            Excluir aqui apaga o banco de dados e o usuario do banco no cPanel - nao tem como desfazer.
            O subdominio precisa ser removido manualmente pelo cPanel (essa hospedagem nao suporta exclusao de subdominio via API).
        </p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success">
        <i class="bi bi-check-circle"></i>
        <div>
            <?php foreach ($success as $mensagem): ?>
                <div><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="crud-alert error">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="dash-panel">
    <?php if ($igrejas === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-building"></i></div>
            <h2>Nenhuma igreja provisionada ainda</h2>
            <p>Igrejas criadas pelo cadastro publico automatico aparecem aqui.</p>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Igreja</th>
                        <th>Subdominio</th>
                        <th>Plano</th>
                        <th>Pagamento</th>
                        <th>Status</th>
                        <th>Criada em</th>
                        <th class="actions-col">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($igrejas as $igreja): ?>
                        <tr>
                            <td>
                                <div class="crud-person">
                                    <span class="crud-avatar">
                                        <?= htmlspecialchars(mb_strtoupper(mb_substr($igreja->nomeIgreja, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span><?= htmlspecialchars($igreja->nomeIgreja, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td>
                                <a href="https://<?= htmlspecialchars($igreja->subdominio, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    <?= htmlspecialchars($igreja->subdominio, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars(Plano::label($igreja->plano), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $igreja->metodoPagamento === 'pix' ? 'Pix' : 'Cartao' ?></td>
                            <td>
                                <span class="status-badge <?= $igreja->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= htmlspecialchars($statusLabel[$igreja->status] ?? $igreja->status, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="crud-text-dim"><?= (new DateTimeImmutable($igreja->criadoEm))->format('d/m/Y') ?></td>
                            <td class="actions-col">
                                <form
                                    method="POST"
                                    action="<?= $basePath ?>/plataforma/igrejas/<?= $igreja->id ?>/excluir"
                                    data-confirm="ATENCAO: isso vai apagar PERMANENTEMENTE a igreja &quot;<?= htmlspecialchars($igreja->nomeIgreja, ENT_QUOTES, 'UTF-8') ?>&quot; - banco de dados (todos os membros/cultos/dados) e usuario do banco no cPanel. Nao tem como desfazer. Voce ainda vai precisar remover o subdominio &quot;<?= htmlspecialchars($igreja->subdominio, ENT_QUOTES, 'UTF-8') ?>&quot; manualmente pelo cPanel depois. Confirma?"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button
                                        type="submit"
                                        class="crud-icon-btn danger"
                                        aria-label="Excluir <?= htmlspecialchars($igreja->nomeIgreja, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
