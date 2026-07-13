<?php
/**
 * @var array $config
 * @var \Igrejas\Models\Ministerio|null $ministerio
 * @var array<int, \Igrejas\Models\Membro> $voluntarios
 * @var array<int, \Igrejas\Models\Membro> $membrosAtivos
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $ministerio !== null;

$nome = $old['nome'] ?? $ministerio->nome ?? '';
$descricao = $old['descricao'] ?? $ministerio->descricao ?? '';
$liderMembroId = $old['lider_membro_id'] ?? $ministerio->liderMembroId ?? '';
$status = $old['status'] ?? $ministerio->status ?? 'ativo';

$actionUrl = $isEdit
    ? $basePath . '/dashboard/ministerios/' . $ministerio->id
    : $basePath . '/dashboard/ministerios';

$voluntarioIds = array_map(static fn ($membro) => $membro->id, $voluntarios);
$disponiveisParaVoluntario = array_filter(
    $membrosAtivos,
    static fn ($membro) => !in_array($membro->id, $voluntarioIds, true)
);
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar ministério' : 'Novo ministério' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize os dados de ' . htmlspecialchars($ministerio->nome, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados para cadastrar um novo ministério.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/ministerios" class="btn-k btn-k-ghost">
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
            <h3><i class="bi bi-diagram-3"></i> Dados do ministério</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="nome">Nome do ministério *</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Ministério de Louvor" required autofocus>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="3" placeholder="Propósito e atividades do ministério (opcional)"><?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="crud-field">
                    <label for="lider_membro_id">Líder</label>
                    <select id="lider_membro_id" name="lider_membro_id">
                        <option value="">Sem líder definido</option>
                        <?php foreach ($membrosAtivos as $membro): ?>
                            <option value="<?= $membro->id ?>" <?= (string) $liderMembroId === (string) $membro->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= $status === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/ministerios" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alterações' : 'Cadastrar ministério' ?>
            </button>
        </div>
    </form>
</div>

<?php if ($isEdit): ?>
    <div class="dash-panel" style="margin-top: 1.4rem;">
        <div class="dash-panel-head">
            <h2><i class="bi bi-people"></i> Voluntários</h2>
            <span class="panel-badge"><?= count($voluntarios) ?> vinculado<?= count($voluntarios) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($disponiveisParaVoluntario !== []): ?>
            <form method="POST" action="<?= $basePath ?>/dashboard/ministerios/<?= $ministerio->id ?>/voluntarios" class="crud-inline-form">
                <?= $csrf ?>
                <select name="membro_id" required>
                    <option value="" selected disabled>Selecione um membro...</option>
                    <?php foreach ($disponiveisParaVoluntario as $membro): ?>
                        <option value="<?= $membro->id ?>"><?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-k btn-k-ghost">
                    <i class="bi bi-plus-lg"></i> Adicionar voluntário
                </button>
            </form>
        <?php endif; ?>

        <?php if ($voluntarios === []): ?>
            <p class="crud-text-dim" style="margin-top: 1rem;">Nenhum voluntário vinculado a este ministério ainda.</p>
        <?php else: ?>
            <ul class="crud-people-list">
                <?php foreach ($voluntarios as $membro): ?>
                    <li>
                        <div class="crud-person">
                            <span class="crud-avatar">
                                <?= htmlspecialchars(mb_strtoupper(mb_substr($membro->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span><?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <form
                            method="POST"
                            action="<?= $basePath ?>/dashboard/ministerios/<?= $ministerio->id ?>/voluntarios/<?= $membro->id ?>/remover"
                            data-confirm="Remover <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?> deste ministério?"
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
