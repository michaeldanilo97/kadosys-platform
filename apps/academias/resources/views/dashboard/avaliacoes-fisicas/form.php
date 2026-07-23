<?php

use Academias\Core\Csrf;
use Academias\Models\Aluno;
use Academias\Models\AvaliacaoFisica;
use Academias\Models\Professor;

/**
 * @var array $config
 * @var AvaliacaoFisica|null $avaliacao
 * @var array<int, Aluno> $alunos
 * @var array<int, Professor> $professores
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $avaliacao !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/avaliacoes-fisicas/' . $avaliacao->id : $basePath . '/dashboard/avaliacoes-fisicas';

$campo = static function (string $campo, mixed $valorAtual) use ($old): string {
    $valor = array_key_exists($campo, $old) ? $old[$campo] : $valorAtual;

    return htmlspecialchars($valor !== null ? (string) $valor : '', ENT_QUOTES, 'UTF-8');
};
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Treino</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar avaliação física' : 'Nova avaliação física' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/avaliacoes-fisicas" class="btn-k btn-k-outline">Voltar</a>
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
            <p class="crud-empty">Cadastre um aluno antes de registrar uma avaliação física.</p>
        <?php else: ?>
            <form method="POST" action="<?= $actionUrl ?>">
                <?= Csrf::field() ?>

                <div class="crud-form-grid">
                    <div class="form-field">
                        <label for="aluno_id">Aluno</label>
                        <select id="aluno_id" name="aluno_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($alunos as $opcaoAluno): ?>
                                <option value="<?= $opcaoAluno->id ?>" <?= $campo('aluno_id', $avaliacao->alunoId ?? '') === (string) $opcaoAluno->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($opcaoAluno->nome, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="professor_id">Professor (opcional)</label>
                        <select id="professor_id" name="professor_id">
                            <option value="">Sem professor definido</option>
                            <?php foreach ($professores as $professor): ?>
                                <option value="<?= $professor->id ?>" <?= $campo('professor_id', $avaliacao->professorId ?? '') === (string) $professor->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($professor->nome, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="data_avaliacao">Data da avaliação</label>
                        <input type="date" id="data_avaliacao" name="data_avaliacao" value="<?= $campo('data_avaliacao', $avaliacao->dataAvaliacao ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="peso_kg">Peso (kg)</label>
                        <input type="text" id="peso_kg" name="peso_kg" value="<?= $campo('peso_kg', $avaliacao->pesoKg ?? '') ?>" placeholder="Ex.: 72.5" required>
                    </div>
                    <div class="form-field">
                        <label for="percentual_gordura">% de gordura (opcional)</label>
                        <input type="text" id="percentual_gordura" name="percentual_gordura" value="<?= $campo('percentual_gordura', $avaliacao->percentualGordura ?? '') ?>" placeholder="Ex.: 18.5">
                    </div>
                    <div class="form-field">
                        <label for="medida_peito_cm">Peito (cm)</label>
                        <input type="text" id="medida_peito_cm" name="medida_peito_cm" value="<?= $campo('medida_peito_cm', $avaliacao->medidaPeitoCm ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="medida_cintura_cm">Cintura (cm)</label>
                        <input type="text" id="medida_cintura_cm" name="medida_cintura_cm" value="<?= $campo('medida_cintura_cm', $avaliacao->medidaCinturaCm ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="medida_quadril_cm">Quadril (cm)</label>
                        <input type="text" id="medida_quadril_cm" name="medida_quadril_cm" value="<?= $campo('medida_quadril_cm', $avaliacao->medidaQuadrilCm ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="medida_braco_cm">Braço (cm)</label>
                        <input type="text" id="medida_braco_cm" name="medida_braco_cm" value="<?= $campo('medida_braco_cm', $avaliacao->medidaBracoCm ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="medida_coxa_cm">Coxa (cm)</label>
                        <input type="text" id="medida_coxa_cm" name="medida_coxa_cm" value="<?= $campo('medida_coxa_cm', $avaliacao->medidaCoxaCm ?? '') ?>">
                    </div>
                    <div class="form-field crud-field-full">
                        <label for="observacao">Observação (opcional)</label>
                        <textarea id="observacao" name="observacao" rows="3"><?= $campo('observacao', $avaliacao->observacao ?? '') ?></textarea>
                    </div>
                </div>

                <div class="crud-form-actions">
                    <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Registrar avaliação' ?></button>
                    <a href="<?= $basePath ?>/dashboard/avaliacoes-fisicas" class="btn-k btn-k-outline">Cancelar</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
