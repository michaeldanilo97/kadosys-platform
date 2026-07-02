<?php
/**
 * @var array $config
 * @var \Igrejas\Models\Culto|null $culto
 * @var array<int, \Igrejas\Models\Membro> $presentes
 * @var array<int, \Igrejas\Models\Membro> $membrosAtivos
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $culto !== null;

$titulo = $old['titulo'] ?? $culto->titulo ?? '';
$data = $old['data'] ?? $culto->data ?? '';
$hora = $old['hora'] ?? $culto->hora ?? '';
$local = $old['local'] ?? $culto->local ?? '';
$descricao = $old['descricao'] ?? $culto->descricao ?? '';
$status = $old['status'] ?? $culto->status ?? 'agendado';

// Inputs type="time" esperam HH:MM; a coluna hora vem como HH:MM:SS do banco.
if ($hora !== '' && strlen($hora) > 5) {
    $hora = substr($hora, 0, 5);
}

$actionUrl = $isEdit
    ? $basePath . '/dashboard/cultos/' . $culto->id
    : $basePath . '/dashboard/cultos';

$presentesIds = array_map(static fn ($membro) => $membro->id, $presentes);
$disponiveisParaPresenca = array_filter(
    $membrosAtivos,
    static fn ($membro) => !in_array($membro->id, $presentesIds, true)
);
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar culto' : 'Novo culto' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize os dados de ' . htmlspecialchars($culto->titulo, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados para agendar um novo culto.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/cultos" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

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
    <form method="POST" action="<?= $actionUrl ?>" class="crud-form">
        <?= $csrf ?>

        <div class="crud-form-section">
            <h3><i class="bi bi-calendar2-week"></i> Dados do culto</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="titulo">Titulo *</label>
                    <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Culto de Celebracao" required autofocus>
                </div>
                <div class="crud-field">
                    <label for="data">Data *</label>
                    <input type="date" id="data" name="data" value="<?= htmlspecialchars($data, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="crud-field">
                    <label for="hora">Horario</label>
                    <input type="time" id="hora" name="hora" value="<?= htmlspecialchars($hora, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="local">Local</label>
                    <input type="text" id="local" name="local" value="<?= htmlspecialchars($local, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Templo principal">
                </div>
                <div class="crud-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="agendado" <?= $status === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                        <option value="realizado" <?= $status === 'realizado' ? 'selected' : '' ?>>Realizado</option>
                        <option value="cancelado" <?= $status === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="descricao">Descricao</label>
                    <textarea id="descricao" name="descricao" rows="3" placeholder="Tema, pregador ou observacoes (opcional)"><?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/cultos" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alteracoes' : 'Agendar culto' ?>
            </button>
        </div>
    </form>
</div>

<?php if ($isEdit): ?>
    <div class="dash-panel" style="margin-top: 1.4rem;">
        <div class="dash-panel-head">
            <h2><i class="bi bi-people"></i> Frequencia</h2>
            <span class="panel-badge"><?= count($presentes) ?> presente<?= count($presentes) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($disponiveisParaPresenca !== []): ?>
            <form method="POST" action="<?= $basePath ?>/dashboard/cultos/<?= $culto->id ?>/presencas" class="crud-inline-form">
                <?= $csrf ?>
                <select name="membro_id" required>
                    <option value="" selected disabled>Selecione um membro...</option>
                    <?php foreach ($disponiveisParaPresenca as $membro): ?>
                        <option value="<?= $membro->id ?>"><?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-k btn-k-ghost">
                    <i class="bi bi-plus-lg"></i> Registrar presenca
                </button>
            </form>
        <?php endif; ?>

        <?php if ($presentes === []): ?>
            <p class="crud-text-dim" style="margin-top: 1rem;">Nenhuma presenca registrada para este culto ainda.</p>
        <?php else: ?>
            <ul class="crud-people-list">
                <?php foreach ($presentes as $membro): ?>
                    <li>
                        <div class="crud-person">
                            <span class="crud-avatar">
                                <?= htmlspecialchars(mb_strtoupper(mb_substr($membro->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span><?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <form
                            method="POST"
                            action="<?= $basePath ?>/dashboard/cultos/<?= $culto->id ?>/presencas/<?= $membro->id ?>/remover"
                            data-confirm="Remover a presenca de <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>?"
                        >
                            <?= $csrf ?>
                            <button type="submit" class="crud-icon-btn danger" aria-label="Remover <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>
