<?php
/**
 * @var array $config
 * @var \Igrejas\Models\AgendaEvento|null $evento
 * @var array<int, \Igrejas\Models\Membro> $membrosAtivos
 * @var bool $podeTornarPublico
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $evento !== null;

$titulo = $old['titulo'] ?? $evento->titulo ?? '';
$tipo = $old['tipo'] ?? $evento->tipo ?? 'evento';
$data = $old['data'] ?? $evento->data ?? '';
$horaInicio = $old['hora_inicio'] ?? $evento->horaInicio ?? '';
$horaFim = $old['hora_fim'] ?? $evento->horaFim ?? '';
$local = $old['local'] ?? $evento->local ?? '';
$responsavelMembroId = $old['responsavel_membro_id'] ?? $evento->responsavelMembroId ?? '';
$descricao = $old['descricao'] ?? $evento->descricao ?? '';
$status = $old['status'] ?? $evento->status ?? 'agendado';
$visibilidade = $old['visibilidade'] ?? $evento->visibilidade ?? ($podeTornarPublico ? 'publico' : 'privado');

// Inputs type="time" esperam HH:MM; a coluna vem como HH:MM:SS do banco.
if ($horaInicio !== '' && strlen($horaInicio) > 5) {
    $horaInicio = substr($horaInicio, 0, 5);
}
if ($horaFim !== '' && strlen($horaFim) > 5) {
    $horaFim = substr($horaFim, 0, 5);
}

$actionUrl = $isEdit
    ? $basePath . '/dashboard/agenda/' . $evento->id
    : $basePath . '/dashboard/agenda';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar evento' : 'Novo evento' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize os dados de ' . htmlspecialchars($evento->titulo, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados para agendar um evento, reunião ou reserva de espaço.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/agenda" class="btn-k btn-k-ghost">
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
            <h3><i class="bi bi-calendar3"></i> Dados do evento</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="titulo">Título *</label>
                    <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Reunião de liderança" required autofocus>
                </div>
                <div class="crud-field">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo">
                        <option value="evento" <?= $tipo === 'evento' ? 'selected' : '' ?>>Evento</option>
                        <option value="reuniao" <?= $tipo === 'reuniao' ? 'selected' : '' ?>>Reunião</option>
                        <option value="reserva" <?= $tipo === 'reserva' ? 'selected' : '' ?>>Reserva de espaço</option>
                        <option value="outro" <?= $tipo === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="data">Data *</label>
                    <input type="date" id="data" name="data" value="<?= htmlspecialchars($data, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="crud-field">
                    <label for="hora_inicio">Horário de início</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" value="<?= htmlspecialchars($horaInicio, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="hora_fim">Horário de término</label>
                    <input type="time" id="hora_fim" name="hora_fim" value="<?= htmlspecialchars($horaFim, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="local">Local</label>
                    <input type="text" id="local" name="local" value="<?= htmlspecialchars($local, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Salão social">
                </div>
                <div class="crud-field">
                    <label for="responsavel_membro_id">Responsável</label>
                    <select id="responsavel_membro_id" name="responsavel_membro_id">
                        <option value="">Não definido</option>
                        <?php foreach ($membrosAtivos as $membro): ?>
                            <option value="<?= $membro->id ?>" <?= (string) $responsavelMembroId === (string) $membro->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="3" placeholder="Detalhes adicionais (opcional)"><?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="crud-field crud-field-full">
                    <label>Quem pode ver este evento?</label>
                    <div style="display: flex; gap: 1.4rem; margin-top: 0.3rem;">
                        <label style="display: flex; align-items: center; gap: 0.4rem; font-weight: 400;">
                            <input type="radio" name="visibilidade" value="publico" <?= $visibilidade === 'publico' ? 'checked' : '' ?> <?= $podeTornarPublico ? '' : 'disabled' ?>>
                            Todo mundo (aparece no calendário de todos)
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.4rem; font-weight: 400;">
                            <input type="radio" name="visibilidade" value="privado" <?= $visibilidade === 'privado' ? 'checked' : '' ?>>
                            Só eu (compromisso pessoal)
                        </label>
                    </div>
                    <?php if (!$podeTornarPublico): ?>
                        <p class="crud-field-hint">
                            Sem edição liberada no módulo Agenda, você só pode cadastrar compromissos pessoais (visíveis só pra você).
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/agenda" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alterações' : 'Agendar evento' ?>
            </button>
        </div>
    </form>
</div>
