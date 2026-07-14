<?php
/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca|null $crianca
 * @var array<int, \Igrejas\Models\KidsTurma> $turmasAtivas
 * @var array<int, \Igrejas\Models\Membro> $membrosAtivos
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $crianca !== null;

$nome = $old['nome'] ?? $crianca->nome ?? '';
$dataNascimento = $old['data_nascimento'] ?? $crianca->dataNascimento ?? '';
$genero = $old['genero'] ?? $crianca->genero ?? '';
$turmaId = $old['turma_id'] ?? $crianca->turmaId ?? '';
$responsavelMembroId = $old['responsavel_membro_id'] ?? $crianca->responsavelMembroId ?? '';
$responsavelNome = $old['responsavel_nome'] ?? $crianca->responsavelNome ?? '';
$responsavelTelefone = $old['responsavel_telefone'] ?? $crianca->responsavelTelefone ?? '';
$autorizadosRetirada = $old['autorizados_retirada'] ?? $crianca->autorizadosRetirada ?? '';
$alergias = $old['alergias'] ?? $crianca->alergias ?? '';
$observacoesMedicas = $old['observacoes_medicas'] ?? $crianca->observacoesMedicas ?? '';
$observacoes = $old['observacoes'] ?? $crianca->observacoes ?? '';
$status = $old['status'] ?? $crianca->status ?? 'ativo';

$actionUrl = $isEdit
    ? $basePath . '/dashboard/kids/criancas/' . $crianca->id
    : $basePath . '/dashboard/kids/criancas';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar criança' : 'Nova criança' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize os dados de ' . htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados para cadastrar uma nova criança no ministério infantil.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $isEdit ? $basePath . '/dashboard/kids/criancas/' . $crianca->id : $basePath . '/dashboard/kids/criancas' ?>" class="btn-k btn-k-ghost">
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
    <form method="POST" action="<?= $actionUrl ?>" class="crud-form" enctype="multipart/form-data">
        <?= $csrf ?>

        <div class="crud-form-section">
            <h3><i class="bi bi-emoji-laughing"></i> Dados da criança</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="nome">Nome da criança *</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars((string) $nome, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome completo" required autofocus>
                </div>
                <div class="crud-field">
                    <label for="foto">Foto</label>
                    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="crud-field">
                    <label for="data_nascimento">Data de nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars((string) $dataNascimento, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="genero">Gênero</label>
                    <select id="genero" name="genero">
                        <option value="">Não informado</option>
                        <option value="masculino" <?= $genero === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                        <option value="feminino" <?= $genero === 'feminino' ? 'selected' : '' ?>>Feminino</option>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="turma_id">Turma</label>
                    <select id="turma_id" name="turma_id">
                        <option value="">Sem turma definida</option>
                        <?php foreach ($turmasAtivas as $turma): ?>
                            <option value="<?= $turma->id ?>" <?= (string) $turmaId === (string) $turma->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($turma->nome, ENT_QUOTES, 'UTF-8') ?>
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

        <div class="crud-form-section">
            <h3><i class="bi bi-person-heart"></i> Responsável</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="responsavel_membro_id">Responsável já cadastrado em Membros</label>
                    <select id="responsavel_membro_id" name="responsavel_membro_id">
                        <option value="">Nenhum (usar nome/telefone avulso abaixo)</option>
                        <?php foreach ($membrosAtivos as $membro): ?>
                            <option value="<?= $membro->id ?>" <?= (string) $responsavelMembroId === (string) $membro->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="responsavel_nome">Nome do responsável (se não for membro)</label>
                    <input type="text" id="responsavel_nome" name="responsavel_nome" value="<?= htmlspecialchars((string) $responsavelNome, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Mãe, pai ou responsável">
                </div>
                <div class="crud-field">
                    <label for="responsavel_telefone">Telefone do responsável</label>
                    <input type="text" id="responsavel_telefone" name="responsavel_telefone" value="<?= htmlspecialchars((string) $responsavelTelefone, ENT_QUOTES, 'UTF-8') ?>" placeholder="(00) 00000-0000">
                </div>
                <div class="crud-field crud-field-full">
                    <label for="autorizados_retirada">Outras pessoas autorizadas a retirar a criança</label>
                    <textarea id="autorizados_retirada" name="autorizados_retirada" rows="2" placeholder="Nomes de quem mais pode buscar a criança, além do responsável acima (opcional)"><?= htmlspecialchars((string) $autorizadosRetirada, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-shield-plus"></i> Saúde e segurança</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="alergias">Alergias</label>
                    <textarea id="alergias" name="alergias" rows="2" placeholder="Alimentos, medicamentos ou outras alergias (opcional)"><?= htmlspecialchars((string) $alergias, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="observacoes_medicas">Observações médicas</label>
                    <textarea id="observacoes_medicas" name="observacoes_medicas" rows="2" placeholder="Condições, medicações contínuas, restrições (opcional)"><?= htmlspecialchars((string) $observacoesMedicas, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="observacoes">Observações gerais</label>
                    <textarea id="observacoes" name="observacoes" rows="2" placeholder="Outras informações úteis pra equipe (opcional)"><?= htmlspecialchars((string) $observacoes, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/kids/criancas" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alterações' : 'Cadastrar criança' ?>
            </button>
        </div>
    </form>
</div>
