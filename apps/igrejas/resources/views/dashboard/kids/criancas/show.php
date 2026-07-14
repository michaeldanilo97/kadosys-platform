<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var bool $checkinAberto
 * @var array<int, \Igrejas\Models\KidsCheckin> $historicoCheckins
 * @var string|null $success
 * @var array $errors
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/kids.css?v=<?= View::assetVersion('assets/css/kids.css') ?>">

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="dash-page-subtitle">Perfil da criança no ministério infantil.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/kids/criancas" class="btn-k btn-k-ghost"><i class="bi bi-arrow-left"></i> Voltar</a>
        <a href="<?= $basePath ?>/dashboard/kids/criancas/<?= $crianca->id ?>/editar" class="btn-k btn-k-grad"><i class="bi bi-pencil"></i> Editar</a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
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

<div class="kids-perfil-header">
    <div class="kids-perfil-foto">
        <?php if ($crianca->fotoPath !== null): ?>
            <img src="<?= $basePath ?>/<?= htmlspecialchars($crianca->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?>">
        <?php else: ?>
            <span class="kids-crianca-card-inicial"><?= htmlspecialchars(mb_strtoupper(mb_substr($crianca->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>
    <div class="kids-perfil-info">
        <div class="kids-perfil-nome-linha">
            <h2><?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?></h2>
            <span class="status-badge <?= $crianca->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                <?= $crianca->status === 'ativo' ? 'Ativo' : 'Inativo' ?>
            </span>
            <?php if ($checkinAberto): ?>
                <span class="status-badge is-ativo"><i class="bi bi-door-open"></i> Na sala agora</span>
            <?php endif; ?>
        </div>
        <p class="crud-text-dim">
            <?= $crianca->turmaNome !== null ? htmlspecialchars($crianca->turmaNome, ENT_QUOTES, 'UTF-8') : 'Sem turma definida' ?>
            <?= $crianca->idade() !== null ? ' &middot; ' . $crianca->idade() . ' anos' : '' ?>
        </p>
        <div class="kids-perfil-stats">
            <span title="Experiência"><i class="bi bi-star-fill"></i> <?= $crianca->xp ?> XP</span>
            <span title="Moedas"><i class="bi bi-coin"></i> <?= $crianca->moedas ?> moedas</span>
            <span title="Sequência de presença"><i class="bi bi-fire"></i> <?= $crianca->sequenciaDias ?> de sequência</span>
        </div>
    </div>
</div>

<div class="dash-panels-row">
    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-person-heart"></i> Responsável</h2>
        </div>
        <?php if ($crianca->nomeResponsavel() !== null): ?>
            <p><strong><?= htmlspecialchars($crianca->nomeResponsavel(), ENT_QUOTES, 'UTF-8') ?></strong></p>
            <?php if ($crianca->responsavelTelefone): ?>
                <p class="crud-text-dim"><i class="bi bi-telephone"></i> <?= htmlspecialchars($crianca->responsavelTelefone, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p class="crud-text-dim">Nenhum responsável cadastrado.</p>
        <?php endif; ?>
        <?php if ($crianca->autorizadosRetirada): ?>
            <p style="margin-top: 0.8rem;"><strong>Também autorizados a retirar:</strong></p>
            <p class="crud-text-dim"><?= nl2br(htmlspecialchars($crianca->autorizadosRetirada, ENT_QUOTES, 'UTF-8')) ?></p>
        <?php endif; ?>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-shield-plus"></i> Saúde e segurança</h2>
        </div>
        <?php if ($crianca->alergias): ?>
            <p><strong><i class="bi bi-exclamation-triangle" style="color: var(--warning, #f59e0b);"></i> Alergias:</strong></p>
            <p class="crud-text-dim"><?= nl2br(htmlspecialchars($crianca->alergias, ENT_QUOTES, 'UTF-8')) ?></p>
        <?php endif; ?>
        <?php if ($crianca->observacoesMedicas): ?>
            <p style="margin-top: 0.8rem;"><strong>Observações médicas:</strong></p>
            <p class="crud-text-dim"><?= nl2br(htmlspecialchars($crianca->observacoesMedicas, ENT_QUOTES, 'UTF-8')) ?></p>
        <?php endif; ?>
        <?php if (!$crianca->alergias && !$crianca->observacoesMedicas): ?>
            <p class="crud-text-dim">Nenhuma informação de saúde registrada.</p>
        <?php endif; ?>
        <?php if ($crianca->observacoes): ?>
            <p style="margin-top: 0.8rem;"><strong>Observações gerais:</strong></p>
            <p class="crud-text-dim"><?= nl2br(htmlspecialchars($crianca->observacoes, ENT_QUOTES, 'UTF-8')) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="dash-panel" style="margin-top: 1.4rem;">
    <div class="dash-panel-head">
        <h2><i class="bi bi-clock-history"></i> Histórico de check-in</h2>
        <span class="panel-badge"><?= count($historicoCheckins) ?> registro<?= count($historicoCheckins) === 1 ? '' : 's' ?></span>
    </div>
    <?php if ($historicoCheckins === []): ?>
        <p class="crud-text-dim">Nenhum check-in registrado ainda.</p>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Entrada</th>
                        <th>Saída</th>
                        <th>Entregue por</th>
                        <th>Retirado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historicoCheckins as $checkin): ?>
                        <tr>
                            <td><?= (new DateTimeImmutable($checkin->data))->format('d/m/Y') ?></td>
                            <td><?= (new DateTimeImmutable($checkin->horaEntrada))->format('H:i') ?></td>
                            <td>
                                <?= $checkin->horaSaida !== null
                                    ? (new DateTimeImmutable($checkin->horaSaida))->format('H:i')
                                    : '<span class="status-badge is-ativo">Na sala</span>' ?>
                            </td>
                            <td><?= $checkin->entreguePor ? htmlspecialchars($checkin->entreguePor, ENT_QUOTES, 'UTF-8') : '<span class="crud-text-dim">—</span>' ?></td>
                            <td><?= $checkin->retiradoPor ? htmlspecialchars($checkin->retiradoPor, ENT_QUOTES, 'UTF-8') : '<span class="crud-text-dim">—</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="dash-panel" style="margin-top: 1.4rem;">
    <form
        method="POST"
        action="<?= $basePath ?>/dashboard/kids/criancas/<?= $crianca->id ?>/excluir"
        data-confirm="Remover &quot;<?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?>&quot; do cadastro de crianças? Isso também apaga o histórico de check-in."
    >
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn-k btn-k-ghost" style="color: var(--danger, #ef4444);">
            <i class="bi bi-trash"></i> Remover criança
        </button>
    </form>
</div>
