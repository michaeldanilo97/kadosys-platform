<?php

use Academias\Core\Csrf;
use Academias\Models\Aluno;
use Academias\Models\PlanoMatricula;

/**
 * @var array $config
 * @var Aluno|null $aluno
 * @var array<int, PlanoMatricula> $planosMatricula
 * @var array $old
 * @var array<int, string> $errors
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $aluno !== null;
$actionUrl = $isEdit ? $basePath . '/dashboard/alunos/' . $aluno->id : $basePath . '/dashboard/alunos';
?>
<main class="dashboard-main">
    <div class="dash-page-head">
        <div>
            <p class="dashboard-eyebrow">Carteira</p>
            <h1 class="dashboard-title"><?= $isEdit ? 'Editar aluno' : 'Novo aluno' ?></h1>
        </div>
        <a href="<?= $basePath ?>/dashboard/alunos" class="btn-k btn-k-outline">Voltar</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="form-alert">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="glass-card dash-panel">
        <form method="POST" action="<?= $actionUrl ?>">
            <?= Csrf::field() ?>

            <div class="crud-form-grid">
                <div class="form-field crud-field-full">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($old['nome'] ?? $aluno->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <div class="form-field">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($old['telefone'] ?? $aluno->telefone ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="(11) 90000-0000">
                </div>
                <div class="form-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? $aluno->email ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="aluno@email.com">
                </div>
                <div class="form-field">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($old['cpf'] ?? $aluno->cpf ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="000.000.000-00">
                </div>
                <div class="form-field">
                    <label for="data_nascimento">Data de nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars($old['data_nascimento'] ?? $aluno->dataNascimento ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-field">
                    <label for="plano_matricula_id">Plano de matrícula</label>
                    <select id="plano_matricula_id" name="plano_matricula_id">
                        <option value="">Sem plano</option>
                        <?php foreach ($planosMatricula as $plano): ?>
                            <option value="<?= $plano->id ?>" <?= (string) ($old['plano_matricula_id'] ?? $aluno->planoMatriculaId ?? '') === (string) $plano->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($plano->nome, ENT_QUOTES, 'UTF-8') ?> (R$ <?= number_format($plano->preco, 2, ',', '.') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($isEdit): ?>
                    <div class="form-field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="ativo" <?= ($old['status'] ?? $aluno->status) === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="inativo" <?= ($old['status'] ?? $aluno->status) === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                            <option value="suspenso" <?= ($old['status'] ?? $aluno->status) === 'suspenso' ? 'selected' : '' ?>>Suspenso</option>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="form-field">
                    <label for="matricula_inicio">Início da matrícula</label>
                    <input type="date" id="matricula_inicio" name="matricula_inicio" value="<?= htmlspecialchars($old['matricula_inicio'] ?? $aluno->matriculaInicio ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-field">
                    <label for="matricula_vencimento">Vencimento da matrícula</label>
                    <input type="date" id="matricula_vencimento" name="matricula_vencimento" value="<?= htmlspecialchars($old['matricula_vencimento'] ?? $aluno->matriculaVencimento ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-field crud-field-full">
                    <label for="objetivo">Objetivo</label>
                    <input type="text" id="objetivo" name="objetivo" value="<?= htmlspecialchars($old['objetivo'] ?? $aluno->objetivo ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: hipertrofia, emagrecimento, condicionamento...">
                </div>
                <div class="form-field crud-field-full">
                    <label for="observacoes_saude">Restrições de saúde (opcional)</label>
                    <textarea id="observacoes_saude" name="observacoes_saude" rows="3" placeholder="Ex.: lesão no joelho, hipertensão..."><?= htmlspecialchars($old['observacoes_saude'] ?? $aluno->observacoesSaude ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
            <p class="form-hint">Preencha pelo menos um: telefone, e-mail ou CPF.</p>

            <div class="crud-form-actions">
                <button type="submit" class="btn-k btn-k-grad"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <a href="<?= $basePath ?>/dashboard/alunos" class="btn-k btn-k-outline">Cancelar</a>
            </div>
        </form>
    </div>
</main>
