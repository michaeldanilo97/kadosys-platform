<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\BloqueioAgenda;
use Barbearias\Models\Profissional;

/**
 * @var array $config
 * @var array<int, Profissional> $profissionais
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';

$labelTipo = [
    BloqueioAgenda::TIPO_BLOQUEIO => 'Bloqueio pontual (reunião, compromisso...)',
    BloqueioAgenda::TIPO_FERIAS => 'Férias',
    BloqueioAgenda::TIPO_FOLGA => 'Folga',
];
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Agenda</p>
            <h1 class="dashboard-title">Novo bloqueio</h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/bloqueios" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($profissionais === []): ?>
        <div class="glass-card dash-panel">
            <p class="crud-empty" style="padding:1rem 0;">
                Cadastre pelo menos um <a href="<?= $basePath ?>/dashboard/profissionais/novo">profissional</a> antes de criar um bloqueio.
            </p>
        </div>
    <?php else: ?>
        <div class="glass-card dash-panel">
            <form method="POST" action="<?= $basePath ?>/dashboard/bloqueios">
                <?= Csrf::field() ?>

                <div class="crud-form-grid">
                    <div class="form-field">
                        <label for="profissional_id">Profissional</label>
                        <select id="profissional_id" name="profissional_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($profissionais as $profissional): ?>
                                <option value="<?= $profissional->id ?>" <?= (string) ($old['profissional_id'] ?? '') === (string) $profissional->id ? 'selected' : '' ?>><?= htmlspecialchars($profissional->nome, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo" required>
                            <?php foreach ($labelTipo as $valor => $label): ?>
                                <option value="<?= $valor ?>" <?= ($old['tipo'] ?? '') === $valor ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="data_inicio">Início</label>
                        <input type="datetime-local" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($old['data_inicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="data_fim">Fim</label>
                        <input type="datetime-local" id="data_fim" name="data_fim" value="<?= htmlspecialchars($old['data_fim'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-field crud-field-full">
                        <label for="motivo">Motivo (opcional)</label>
                        <input type="text" id="motivo" name="motivo" value="<?= htmlspecialchars($old['motivo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Consulta médica">
                    </div>
                </div>

                <div class="crud-form-actions">
                    <button type="submit" class="btn-k btn-k-grad">Registrar bloqueio</button>
                    <a href="<?= $basePath ?>/dashboard/bloqueios" class="btn-k btn-k-outline">Cancelar</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</main>
