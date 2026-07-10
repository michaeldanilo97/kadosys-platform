<?php

use Igrejas\Models\PlataformaAviso;

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
            Publique um aviso e escolha quem ve - admins, membros ou todo mundo - util pra manutencao
            programada, novo recurso disponivel, etc.
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
            <div>
                <?= nl2br(htmlspecialchars($avisoAtivo->mensagem, ENT_QUOTES, 'UTF-8')) ?>
                <span class="crud-text-dim" style="display: block; font-size: 0.74rem; margin-top: 0.3rem;">
                    Publico: <?= htmlspecialchars(PlataformaAviso::PUBLICO_LABELS[$avisoAtivo->publicoAlvo] ?? $avisoAtivo->publicoAlvo, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
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
            <div class="crud-field crud-field-full">
                <label>Publico</label>
                <div style="display: flex; gap: 1.2rem; margin-top: 0.3rem;">
                    <?php foreach (PlataformaAviso::PUBLICO_LABELS as $valor => $rotulo): ?>
                        <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-weight: 400;">
                            <input type="radio" name="publico_alvo" value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>" <?= $valor === PlataformaAviso::PUBLICO_TODOS ? 'checked' : '' ?>>
                            <?= htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="crud-form-actions" style="justify-content: flex-start;">
                <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-send"></i> Publicar aviso</button>
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
                        <th>Publico</th>
                        <th>Status</th>
                        <th>Publicado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $aviso): ?>
                        <tr>
                            <td><?= htmlspecialchars($aviso->mensagem, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(PlataformaAviso::PUBLICO_LABELS[$aviso->publicoAlvo] ?? $aviso->publicoAlvo, ENT_QUOTES, 'UTF-8') ?></td>
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
