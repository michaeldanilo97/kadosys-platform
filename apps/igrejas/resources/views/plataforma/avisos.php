<?php

/**
 * @var array $config
 * @var \Igrejas\Models\PlataformaAviso|null $avisoAtivo
 * @var array<int, \Igrejas\Models\PlataformaAviso> $historico
 * @var array<int, string>|null $success
 * @var array $errors
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Avisos para as igrejas</h1>
        <p class="dash-page-subtitle">
            Publique um aviso e ele aparece no sino de notificacoes do painel de <strong>todas</strong> as igrejas cadastradas -
            util pra manutencao programada, novo recurso disponivel, etc.
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
    <div class="dash-panel-head">
        <h2><i class="bi bi-megaphone"></i> <?= $avisoAtivo ? 'Aviso ativo' : 'Publicar um novo aviso' ?></h2>
    </div>

    <?php if ($avisoAtivo): ?>
        <div class="crud-alert" style="background: rgba(59,130,246,0.08); border-color: rgba(59,130,246,0.3);">
            <i class="bi bi-broadcast-pin"></i>
            <div><?= nl2br(htmlspecialchars($avisoAtivo->mensagem, ENT_QUOTES, 'UTF-8')) ?></div>
        </div>

        <form method="POST" action="<?= $basePath ?>/plataforma/avisos/<?= $avisoAtivo->id ?>/encerrar" data-confirm="Encerrar este aviso? Ele deixa de aparecer no painel das igrejas.">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn-k btn-k-outline" style="border-color: rgba(248,113,113,0.4); color: var(--danger);">
                <i class="bi bi-x-circle"></i> Encerrar aviso
            </button>
        </form>
    <?php else: ?>
        <form method="POST" action="<?= $basePath ?>/plataforma/avisos" class="crud-form">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="crud-field crud-field-full">
                <label for="mensagem">Mensagem</label>
                <textarea id="mensagem" name="mensagem" rows="3" placeholder="Ex.: Manutencao programada hoje as 22h - o sistema pode ficar indisponivel por alguns minutos." required autofocus></textarea>
            </div>
            <div class="crud-form-actions" style="justify-content: flex-start;">
                <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-send"></i> Publicar para todas as igrejas</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if ($historico !== []): ?>
    <div class="dash-panel" style="margin-top: 1.2rem;">
        <div class="dash-panel-head">
            <h2><i class="bi bi-clock-history"></i> Historico</h2>
        </div>

        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Mensagem</th>
                        <th>Status</th>
                        <th>Publicado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $aviso): ?>
                        <tr>
                            <td><?= htmlspecialchars($aviso->mensagem, ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="status-badge <?= $aviso->ativo ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $aviso->ativo ? 'Ativo' : 'Encerrado' ?>
                                </span>
                            </td>
                            <td class="crud-text-dim"><?= (new DateTimeImmutable($aviso->createdAt))->format('d/m/Y H:i') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
