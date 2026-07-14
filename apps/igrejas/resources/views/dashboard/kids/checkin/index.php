<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var array<int, \Igrejas\Models\KidsCrianca> $criancasAtivas
 * @var array<int, \Igrejas\Models\KidsCheckin> $abertosHoje
 * @var array<int, \Igrejas\Models\KidsCheckin> $encerradosHoje
 * @var string|null $success
 * @var array{nome: string, codigo: string}|null $codigoGerado
 * @var array $errors
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
$idsComCheckinAberto = array_map(static fn ($c) => $c->criancaId, $abertosHoje);
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/kids.css?v=<?= View::assetVersion('assets/css/kids.css') ?>">

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Kids &middot; Check-in</h1>
        <p class="dash-page-subtitle">Registre a entrada e a saída das crianças na sala.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/kids" class="btn-k btn-k-ghost"><i class="bi bi-arrow-left"></i> Kids</a>
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

<?php if ($codigoGerado !== null): ?>
    <div class="kids-codigo-banner">
        <div class="kids-codigo-banner-icone"><i class="bi bi-shield-lock-fill"></i></div>
        <div>
            <div class="kids-codigo-banner-titulo">
                Código de retirada de <?= htmlspecialchars($codigoGerado['nome'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="kids-codigo-banner-codigo"><?= htmlspecialchars($codigoGerado['codigo'], ENT_QUOTES, 'UTF-8') ?></div>
            <p>Entregue este código ao responsável. Ele será solicitado na hora da retirada.</p>
        </div>
    </div>
<?php endif; ?>

<div class="dash-panel">
    <div class="dash-panel-head">
        <h2><i class="bi bi-box-arrow-in-right"></i> Registrar entrada</h2>
    </div>
    <form method="POST" action="<?= $basePath ?>/dashboard/kids/checkin" class="crud-inline-form">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <select name="crianca_id" required>
            <option value="" selected disabled>Selecione a criança...</option>
            <?php foreach ($criancasAtivas as $crianca): ?>
                <option value="<?= $crianca->id ?>" <?= in_array($crianca->id, $idsComCheckinAberto, true) ? 'disabled' : '' ?>>
                    <?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?>
                    <?= $crianca->turmaNome !== null ? ' (' . htmlspecialchars($crianca->turmaNome, ENT_QUOTES, 'UTF-8') . ')' : '' ?>
                    <?= in_array($crianca->id, $idsComCheckinAberto, true) ? ' — já está na sala' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="entregue_por" placeholder="Entregue por (opcional)">
        <button type="submit" class="btn-k btn-k-grad">
            <i class="bi bi-box-arrow-in-right"></i> Fazer check-in
        </button>
    </form>
    <?php if ($criancasAtivas === []): ?>
        <p class="crud-text-dim" style="margin-top: 1rem;">
            Nenhuma criança cadastrada ainda.
            <a href="<?= $basePath ?>/dashboard/kids/criancas/novo">Cadastrar a primeira</a>.
        </p>
    <?php endif; ?>
</div>

<div class="dash-panel" style="margin-top: 1.4rem;">
    <div class="dash-panel-head">
        <h2><i class="bi bi-door-open"></i> Na sala agora</h2>
        <span class="panel-badge"><?= count($abertosHoje) ?></span>
    </div>
    <?php if ($abertosHoje === []): ?>
        <p class="crud-text-dim">Nenhuma criança na sala no momento.</p>
    <?php else: ?>
        <ul class="kids-checkin-lista">
            <?php foreach ($abertosHoje as $checkin): ?>
                <li class="kids-checkin-item">
                    <div class="crud-person">
                        <span class="crud-avatar">
                            <?php if ($checkin->criancaFotoPath !== null): ?>
                                <img src="<?= $basePath ?>/<?= htmlspecialchars($checkin->criancaFotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
                            <?php else: ?>
                                <?= htmlspecialchars(mb_strtoupper(mb_substr((string) $checkin->criancaNome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </span>
                        <span>
                            <?= htmlspecialchars((string) $checkin->criancaNome, ENT_QUOTES, 'UTF-8') ?>
                            <div class="crud-text-dim" style="font-size: 0.78rem;">
                                <?= $checkin->turmaNome !== null ? htmlspecialchars($checkin->turmaNome, ENT_QUOTES, 'UTF-8') . ' &middot; ' : '' ?>
                                entrada às <?= (new DateTimeImmutable($checkin->horaEntrada))->format('H:i') ?>
                            </div>
                        </span>
                    </div>
                    <form method="POST" action="<?= $basePath ?>/dashboard/kids/checkin/<?= $checkin->id ?>/encerrar" class="kids-checkin-saida-form">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="codigo_seguranca" placeholder="Código" maxlength="6" required>
                        <input type="text" name="retirado_por" placeholder="Retirado por (opcional)">
                        <button type="submit" class="btn-k btn-k-ghost">
                            <i class="bi bi-box-arrow-right"></i> Confirmar saída
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php if ($encerradosHoje !== []): ?>
    <div class="dash-panel" style="margin-top: 1.4rem;">
        <div class="dash-panel-head">
            <h2><i class="bi bi-check2-circle"></i> Já retiradas hoje</h2>
            <span class="panel-badge"><?= count($encerradosHoje) ?></span>
        </div>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Criança</th>
                        <th>Entrada</th>
                        <th>Saída</th>
                        <th>Retirado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($encerradosHoje as $checkin): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $checkin->criancaNome, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (new DateTimeImmutable($checkin->horaEntrada))->format('H:i') ?></td>
                            <td><?= $checkin->horaSaida !== null ? (new DateTimeImmutable($checkin->horaSaida))->format('H:i') : '—' ?></td>
                            <td><?= $checkin->retiradoPor ? htmlspecialchars($checkin->retiradoPor, ENT_QUOTES, 'UTF-8') : '<span class="crud-text-dim">—</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
