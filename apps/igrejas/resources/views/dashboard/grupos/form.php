<?php
/**
 * @var array $config
 * @var \Igrejas\Models\Grupo|null $grupo
 * @var array<int, \Igrejas\Models\Membro> $participantes
 * @var array<int, \Igrejas\Models\Membro> $membrosAtivos
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $grupo !== null;

$nome = $old['nome'] ?? $grupo->nome ?? '';
$tipo = $old['tipo'] ?? $grupo->tipo ?? 'grupo';
$descricao = $old['descricao'] ?? $grupo->descricao ?? '';
$liderMembroId = $old['lider_membro_id'] ?? $grupo->liderMembroId ?? '';
$diaSemana = $old['dia_semana'] ?? $grupo->diaSemana ?? '';
$horario = $old['horario'] ?? $grupo->horario ?? '';
$local = $old['local'] ?? $grupo->local ?? '';
$status = $old['status'] ?? $grupo->status ?? 'ativo';

$actionUrl = $isEdit
    ? $basePath . '/dashboard/grupos/' . $grupo->id
    : $basePath . '/dashboard/grupos';

$diasSemana = [
    'domingo' => 'Domingo', 'segunda' => 'Segunda-feira', 'terca' => 'Terça-feira',
    'quarta' => 'Quarta-feira', 'quinta' => 'Quinta-feira', 'sexta' => 'Sexta-feira', 'sabado' => 'Sábado',
];

$participanteIds = array_map(static fn ($membro) => $membro->id, $participantes);
$disponiveisParaParticipante = array_filter(
    $membrosAtivos,
    static fn ($membro) => !in_array($membro->id, $participanteIds, true)
);
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar grupo' : 'Novo grupo' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize os dados de ' . htmlspecialchars($grupo->nome, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados para cadastrar um novo grupo, célula ou classe.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/grupos" class="btn-k btn-k-ghost">
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
            <h3><i class="bi bi-people-fill"></i> Dados do grupo</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="nome">Nome do grupo *</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Célula Vila Nova" required autofocus>
                </div>
                <div class="crud-field">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo">
                        <option value="grupo" <?= $tipo === 'grupo' ? 'selected' : '' ?>>Grupo</option>
                        <option value="celula" <?= $tipo === 'celula' ? 'selected' : '' ?>>Célula</option>
                        <option value="classe" <?= $tipo === 'classe' ? 'selected' : '' ?>>Classe</option>
                    </select>
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
                <div class="crud-field crud-field-full">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="3" placeholder="Propósito e atividades do grupo (opcional)"><?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="crud-field">
                    <label for="dia_semana">Dia do encontro</label>
                    <select id="dia_semana" name="dia_semana">
                        <option value="">Não definido</option>
                        <?php foreach ($diasSemana as $valor => $rotulo): ?>
                            <option value="<?= $valor ?>" <?= $diaSemana === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="horario">Horário</label>
                    <input type="time" id="horario" name="horario" value="<?= htmlspecialchars(substr($horario, 0, 5), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field crud-field-full">
                    <label for="local">Local do encontro</label>
                    <input type="text" id="local" name="local" value="<?= htmlspecialchars($local, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Casa da família Silva, sala 3 do templo... (opcional)">
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
            <a href="<?= $basePath ?>/dashboard/grupos" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alterações' : 'Cadastrar grupo' ?>
            </button>
        </div>
    </form>
</div>

<?php if ($isEdit): ?>
    <div class="dash-panel" style="margin-top: 1.4rem;">
        <div class="dash-panel-head">
            <h2><i class="bi bi-people"></i> Participantes</h2>
            <span class="panel-badge"><?= count($participantes) ?> vinculado<?= count($participantes) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($disponiveisParaParticipante !== []): ?>
            <form method="POST" action="<?= $basePath ?>/dashboard/grupos/<?= $grupo->id ?>/participantes" class="crud-inline-form">
                <?= $csrf ?>
                <select name="membro_id" required>
                    <option value="" selected disabled>Selecione um membro...</option>
                    <?php foreach ($disponiveisParaParticipante as $membro): ?>
                        <option value="<?= $membro->id ?>"><?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-k btn-k-ghost">
                    <i class="bi bi-plus-lg"></i> Adicionar participante
                </button>
            </form>
        <?php endif; ?>

        <?php if ($participantes === []): ?>
            <p class="crud-text-dim" style="margin-top: 1rem;">Nenhum participante vinculado a este grupo ainda.</p>
        <?php else: ?>
            <ul class="crud-people-list">
                <?php foreach ($participantes as $membro): ?>
                    <li>
                        <div class="crud-person">
                            <span class="crud-avatar">
                                <?= htmlspecialchars(mb_strtoupper(mb_substr($membro->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span><?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <form
                            method="POST"
                            action="<?= $basePath ?>/dashboard/grupos/<?= $grupo->id ?>/participantes/<?= $membro->id ?>/remover"
                            data-confirm="Remover <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?> deste grupo?"
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
