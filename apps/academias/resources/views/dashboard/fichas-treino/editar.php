<?php

use Academias\Core\Csrf;
use Academias\Models\Aluno;
use Academias\Models\FichaExercicio;
use Academias\Models\FichaTreino;
use Academias\Models\Professor;

/**
 * @var array $config
 * @var FichaTreino $ficha
 * @var Aluno|null $aluno
 * @var array<int, Aluno> $alunos
 * @var array<int, Professor> $professores
 * @var array<int, FichaExercicio> $exercicios
 * @var FichaExercicio|null $exercicioEmEdicao
 * @var string|null $success
 * @var array<int, string> $errors
 * @var array<int, string> $exercicioErrors
 * @var array $exercicioOld
 */
$basePath = $config['base_path'] ?? '';
$acaoExercicio = $exercicioEmEdicao !== null
    ? $basePath . '/dashboard/fichas-treino/' . $ficha->id . '/exercicios/' . $exercicioEmEdicao->id
    : $basePath . '/dashboard/fichas-treino/' . $ficha->id . '/exercicios';

$campoExercicio = static function (string $campo, string $valorAtual) use ($exercicioOld): string {
    return htmlspecialchars($exercicioOld[$campo] ?? $valorAtual, ENT_QUOTES, 'UTF-8');
};
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Treino</p>
            <h1 class="dashboard-title"><?= htmlspecialchars($ficha->nome, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="dash-page-subtitle">Aluno: <?= htmlspecialchars($aluno->nome ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/fichas-treino" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success">
            <div><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <h3 style="font-size: 0.95rem; margin: 0 0 1rem;">Dados da ficha</h3>
        <form method="POST" action="<?= $basePath ?>/dashboard/fichas-treino/<?= $ficha->id ?>">
            <?= Csrf::field() ?>
            <div class="crud-form-grid">
                <div class="form-field">
                    <label for="aluno_id">Aluno</label>
                    <select id="aluno_id" name="aluno_id" required>
                        <?php foreach ($alunos as $opcaoAluno): ?>
                            <option value="<?= $opcaoAluno->id ?>" <?= $opcaoAluno->id === $ficha->alunoId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opcaoAluno->nome, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="professor_id">Professor</label>
                    <select id="professor_id" name="professor_id">
                        <option value="">Sem professor definido</option>
                        <?php foreach ($professores as $professor): ?>
                            <option value="<?= $professor->id ?>" <?= $professor->id === $ficha->professorId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($professor->nome, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field crud-field-full">
                    <label for="nome">Nome da ficha</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($ficha->nome, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-field">
                    <label for="objetivo">Objetivo</label>
                    <input type="text" id="objetivo" name="objetivo" value="<?= htmlspecialchars($ficha->objetivo ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="validade_ate">Válida até</label>
                    <input type="date" id="validade_ate" name="validade_ate" value="<?= htmlspecialchars($ficha->validadeAte ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field crud-checkbox-field">
                    <input type="checkbox" id="ativa" name="ativa" value="1" <?= $ficha->ativa ? 'checked' : '' ?>>
                    <label for="ativa" style="margin:0;">Ficha ativa (aparece no painel do aluno)</label>
                </div>
            </div>
            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad">Salvar dados da ficha</button>
            </div>
        </form>
    </div>

    <div class="glass-card dash-panel">
        <h3 style="font-size: 0.95rem; margin: 0 0 1rem;">Exercícios</h3>

        <?php if ($exercicios === []): ?>
            <p class="crud-empty">Nenhum exercício adicionado ainda.</p>
        <?php else: ?>
            <div class="crud-table-wrapper">
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Exercício</th>
                            <th>Grupo</th>
                            <th>Séries x Reps</th>
                            <th>Carga sugerida</th>
                            <th>Descanso</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exercicios as $exercicio): ?>
                            <tr>
                                <td class="text-dim"><?= $exercicio->ordem ?></td>
                                <td><?= htmlspecialchars($exercicio->nomeExercicio, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= htmlspecialchars($exercicio->grupoMuscular ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $exercicio->series ?? '-' ?>x <?= htmlspecialchars($exercicio->repeticoes ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-dim"><?= $exercicio->cargaSugeridaKg !== null ? number_format($exercicio->cargaSugeridaKg, 1, ',', '.') . ' kg' : '-' ?></td>
                                <td class="text-dim"><?= $exercicio->descansoSegundos !== null ? $exercicio->descansoSegundos . 's' : '-' ?></td>
                                <td class="actions-col">
                                    <a href="<?= $basePath ?>/dashboard/fichas-treino/<?= $ficha->id ?>/editar?exercicio=<?= $exercicio->id ?>" class="crud-icon-btn" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                    <form method="POST" action="<?= $basePath ?>/dashboard/fichas-treino/<?= $ficha->id ?>/exercicios/<?= $exercicio->id ?>/excluir" data-confirm="Remover este exercício?">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="crud-icon-btn danger" title="Excluir"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h3 style="font-size: 0.9rem; margin: 1.75rem 0 1rem;"><?= $exercicioEmEdicao !== null ? 'Editar exercício' : 'Adicionar exercício' ?></h3>

        <?php if ($exercicioErrors !== []): ?>
            <div class="form-alert">
                <?php foreach ($exercicioErrors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $acaoExercicio ?>">
            <?= Csrf::field() ?>
            <div class="crud-form-grid">
                <div class="form-field crud-field-full">
                    <label for="nome_exercicio">Exercício</label>
                    <input type="text" id="nome_exercicio" name="nome_exercicio" value="<?= $campoExercicio('nome_exercicio', $exercicioEmEdicao?->nomeExercicio ?? '') ?>" placeholder="Ex.: Supino reto" required>
                </div>
                <div class="form-field">
                    <label for="grupo_muscular">Grupo muscular</label>
                    <input type="text" id="grupo_muscular" name="grupo_muscular" value="<?= $campoExercicio('grupo_muscular', $exercicioEmEdicao?->grupoMuscular ?? '') ?>" placeholder="Ex.: Peito">
                </div>
                <div class="form-field">
                    <label for="series">Séries</label>
                    <input type="number" id="series" name="series" min="0" value="<?= $campoExercicio('series', (string) ($exercicioEmEdicao?->series ?? '')) ?>">
                </div>
                <div class="form-field">
                    <label for="repeticoes">Repetições</label>
                    <input type="text" id="repeticoes" name="repeticoes" value="<?= $campoExercicio('repeticoes', $exercicioEmEdicao?->repeticoes ?? '') ?>" placeholder="Ex.: 10-12">
                </div>
                <div class="form-field">
                    <label for="carga_sugerida_kg">Carga sugerida (kg)</label>
                    <input type="text" id="carga_sugerida_kg" name="carga_sugerida_kg" value="<?= $campoExercicio('carga_sugerida_kg', $exercicioEmEdicao?->cargaSugeridaKg !== null ? (string) $exercicioEmEdicao->cargaSugeridaKg : '') ?>" placeholder="Ex.: 20">
                </div>
                <div class="form-field">
                    <label for="descanso_segundos">Descanso (segundos)</label>
                    <input type="number" id="descanso_segundos" name="descanso_segundos" min="0" value="<?= $campoExercicio('descanso_segundos', (string) ($exercicioEmEdicao?->descansoSegundos ?? '')) ?>">
                </div>
                <div class="form-field crud-field-full">
                    <label for="observacao">Observação (opcional)</label>
                    <input type="text" id="observacao" name="observacao" value="<?= $campoExercicio('observacao', $exercicioEmEdicao?->observacao ?? '') ?>" placeholder="Ex.: pegada aberta">
                </div>
            </div>
            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $exercicioEmEdicao !== null ? 'Salvar exercício' : 'Adicionar exercício' ?></button>
                <?php if ($exercicioEmEdicao !== null): ?>
                    <a href="<?= $basePath ?>/dashboard/fichas-treino/<?= $ficha->id ?>/editar" class="btn-k btn-k-outline">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</main>
