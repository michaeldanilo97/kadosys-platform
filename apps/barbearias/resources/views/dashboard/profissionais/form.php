<?php

use Barbearias\Core\Csrf;
use Barbearias\Models\Profissional;

/**
 * @var array $config
 * @var Profissional|null $profissional
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $profissional !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/profissionais/' . $profissional->id : $basePath . '/dashboard/profissionais';

$diasSemana = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 0 => 'Dom'];
$diasAtuais = $old['dias_atendimento'] ?? $profissional->diasAtendimento ?? [];
$horarioInicioAtual = $old['horario_inicio'] ?? ($profissional !== null ? substr((string) $profissional->horarioInicio, 0, 5) : '09:00');
$horarioFimAtual = $old['horario_fim'] ?? ($profissional !== null ? substr((string) $profissional->horarioFim, 0, 5) : '18:00');
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Equipe</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar profissional' : 'Novo profissional' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/profissionais" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="POST" action="<?= $actionUrl ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>

            <div class="crud-form-grid">
                <div class="form-field crud-field-full">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $profissional->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <div class="form-field">
                    <label for="especialidade">Especialidade</label>
                    <input type="text" id="especialidade" name="especialidade" value="<?= htmlspecialchars($old['especialidade'] ?? $profissional->especialidade ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Corte masculino, barba">
                </div>
                <div class="form-field">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($old['telefone'] ?? $profissional->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
                <div class="form-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? $profissional->email ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="profissional@email.com">
                </div>
                <div class="form-field">
                    <label for="foto">Foto</label>
                    <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp">
                    <span class="form-field-hint">PNG, JPG ou WEBP - até 5MB.</span>
                    <?php if ($isEdit && $profissional->fotoPath): ?>
                        <img src="<?= $basePath ?>/<?= htmlspecialchars($profissional->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Foto atual" class="foto-preview">
                    <?php endif; ?>
                </div>

                <div class="form-field crud-field-full">
                    <label>Dias de atendimento</label>
                    <div class="dias-semana-grid">
                        <?php foreach ($diasSemana as $numero => $label): ?>
                            <label class="dia-semana-chip">
                                <input type="checkbox" name="dias_atendimento[]" value="<?= $numero ?>" <?= in_array($numero, $diasAtuais, true) ? 'checked' : '' ?>>
                                <span><?= $label ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <span class="form-field-hint">Usado pra calcular os horários disponíveis no agendamento público.</span>
                </div>
                <div class="form-field">
                    <label for="horario_inicio">Início do expediente</label>
                    <input type="time" id="horario_inicio" name="horario_inicio" value="<?= htmlspecialchars($horarioInicioAtual, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="horario_fim">Fim do expediente</label>
                    <input type="time" id="horario_fim" name="horario_fim" value="<?= htmlspecialchars($horarioFimAtual, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <?php if ($isEdit): ?>
                    <div class="form-field crud-checkbox-field">
                        <input type="checkbox" id="ativo" name="ativo" value="1" <?= $profissional->ativo ? 'checked' : '' ?>>
                        <label for="ativo" style="margin:0;">Profissional ativo</label>
                    </div>
                <?php endif; ?>
            </div>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/profissionais" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
