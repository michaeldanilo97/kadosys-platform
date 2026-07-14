<?php
/**
 * @var array $config
 * @var \Igrejas\Models\KidsTurma|null $turma
 * @var array<int, \Igrejas\Models\Membro> $membrosAtivos
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $turma !== null;

$nome = $old['nome'] ?? $turma->nome ?? '';
$faixaMin = $old['faixa_etaria_min'] ?? $turma->faixaEtariaMin ?? '';
$faixaMax = $old['faixa_etaria_max'] ?? $turma->faixaEtariaMax ?? '';
$professorMembroId = $old['professor_membro_id'] ?? $turma->professorMembroId ?? '';
$descricao = $old['descricao'] ?? $turma->descricao ?? '';
$status = $old['status'] ?? $turma->status ?? 'ativo';

$actionUrl = $isEdit
    ? $basePath . '/dashboard/kids/turmas/' . $turma->id
    : $basePath . '/dashboard/kids/turmas';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar turma' : 'Nova turma' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize os dados de ' . htmlspecialchars($turma->nome, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados para cadastrar uma nova turma do ministério infantil.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/kids/turmas" class="btn-k btn-k-ghost">
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
            <h3><i class="bi bi-collection"></i> Dados da turma</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="nome">Nome da turma *</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars((string) $nome, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Berçário, Kids 4-6, Juniores..." required autofocus>
                </div>
                <div class="crud-field">
                    <label for="faixa_etaria_min">Idade mínima</label>
                    <input type="number" id="faixa_etaria_min" name="faixa_etaria_min" min="0" max="17" value="<?= htmlspecialchars((string) $faixaMin, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: 4">
                </div>
                <div class="crud-field">
                    <label for="faixa_etaria_max">Idade máxima</label>
                    <input type="number" id="faixa_etaria_max" name="faixa_etaria_max" min="0" max="17" value="<?= htmlspecialchars((string) $faixaMax, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: 6">
                </div>
                <div class="crud-field">
                    <label for="professor_membro_id">Professor responsável</label>
                    <select id="professor_membro_id" name="professor_membro_id">
                        <option value="">Sem professor definido</option>
                        <?php foreach ($membrosAtivos as $membro): ?>
                            <option value="<?= $membro->id ?>" <?= (string) $professorMembroId === (string) $membro->id ? 'selected' : '' ?>>
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
                <div class="crud-field crud-field-full">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="3" placeholder="Sala, material usado, observações da turma (opcional)"><?= htmlspecialchars((string) $descricao, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/kids/turmas" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alterações' : 'Cadastrar turma' ?>
            </button>
        </div>
    </form>
</div>
