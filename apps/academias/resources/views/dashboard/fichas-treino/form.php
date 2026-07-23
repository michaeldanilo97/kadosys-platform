<?php

use Academias\Core\Csrf;
use Academias\Models\Aluno;
use Academias\Models\Professor;

/**
 * @var array $config
 * @var array<int, Aluno> $alunos
 * @var array<int, Professor> $professores
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Treino</p>
            <h1 class="dashboard-title">Nova ficha de treino</h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/fichas-treino" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <?php if ($alunos === []): ?>
            <p class="crud-empty">Cadastre um aluno antes de criar uma ficha de treino.</p>
        <?php else: ?>
            <form method="POST" action="<?= $basePath ?>/dashboard/fichas-treino">
                <?= Csrf::field() ?>

                <div class="crud-form-grid">
                    <div class="form-field">
                        <label for="aluno_id">Aluno</label>
                        <select id="aluno_id" name="aluno_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?= $aluno->id ?>" <?= (string) ($old['aluno_id'] ?? '') === (string) $aluno->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($aluno->nome, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="professor_id">Professor (opcional)</label>
                        <select id="professor_id" name="professor_id">
                            <option value="">Sem professor definido</option>
                            <?php foreach ($professores as $professor): ?>
                                <option value="<?= $professor->id ?>" <?= (string) ($old['professor_id'] ?? '') === (string) $professor->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($professor->nome, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field crud-field-full">
                        <label for="nome">Nome da ficha</label>
                        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Treino A - Superior" required autofocus>
                    </div>
                    <div class="form-field">
                        <label for="objetivo">Objetivo</label>
                        <input type="text" id="objetivo" name="objetivo" value="<?= htmlspecialchars($old['objetivo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: hipertrofia">
                    </div>
                    <div class="form-field">
                        <label for="validade_ate">Válida até (opcional)</label>
                        <input type="date" id="validade_ate" name="validade_ate" value="<?= htmlspecialchars($old['validade_ate'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <p class="form-hint">Depois de criar, você adiciona os exercícios na tela seguinte.</p>

                <div class="crud-form-actions">
                    <button type="submit" class="btn-k btn-k-grad">Criar ficha</button>
                    <a href="<?= $basePath ?>/dashboard/fichas-treino" class="btn-k btn-k-outline">Cancelar</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
