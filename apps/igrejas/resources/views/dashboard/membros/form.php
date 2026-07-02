<?php
/**
 * @var array $config
 * @var \Igrejas\Models\Membro|null $membro
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $membro !== null;

$nome = $old['nome'] ?? $membro->nome ?? '';
$email = $old['email'] ?? $membro->email ?? '';
$telefone = $old['telefone'] ?? $membro->telefone ?? '';
$dataNascimento = $old['data_nascimento'] ?? $membro->dataNascimento ?? '';
$genero = $old['genero'] ?? $membro->genero ?? '';
$estadoCivil = $old['estado_civil'] ?? $membro->estadoCivil ?? '';
$endereco = $old['endereco'] ?? $membro->endereco ?? '';
$cidade = $old['cidade'] ?? $membro->cidade ?? '';
$estado = $old['estado'] ?? $membro->estado ?? '';
$dataMembresia = $old['data_membresia'] ?? $membro->dataMembresia ?? '';
$status = $old['status'] ?? $membro->status ?? 'ativo';
$observacoes = $old['observacoes'] ?? $membro->observacoes ?? '';

$actionUrl = $isEdit
    ? $basePath . '/dashboard/membros/' . $membro->id
    : $basePath . '/dashboard/membros';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar membro' : 'Novo membro' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize os dados de ' . htmlspecialchars($membro->nome, ENT_QUOTES, 'UTF-8') . '.'
                : 'Preencha os dados para cadastrar um novo membro.' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/membros" class="btn-k btn-k-ghost">
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
            <h3><i class="bi bi-person"></i> Dados pessoais</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="nome">Nome completo *</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome do membro" required autofocus>
                </div>
                <div class="crud-field">
                    <label for="data_nascimento">Data de nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars($dataNascimento, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="genero">Genero</label>
                    <select id="genero" name="genero">
                        <option value="" <?= $genero === '' ? 'selected' : '' ?>>Nao informado</option>
                        <option value="feminino" <?= $genero === 'feminino' ? 'selected' : '' ?>>Feminino</option>
                        <option value="masculino" <?= $genero === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                        <option value="outro" <?= $genero === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="estado_civil">Estado civil</label>
                    <select id="estado_civil" name="estado_civil">
                        <option value="" <?= $estadoCivil === '' ? 'selected' : '' ?>>Nao informado</option>
                        <option value="solteiro" <?= $estadoCivil === 'solteiro' ? 'selected' : '' ?>>Solteiro(a)</option>
                        <option value="casado" <?= $estadoCivil === 'casado' ? 'selected' : '' ?>>Casado(a)</option>
                        <option value="divorciado" <?= $estadoCivil === 'divorciado' ? 'selected' : '' ?>>Divorciado(a)</option>
                        <option value="viuvo" <?= $estadoCivil === 'viuvo' ? 'selected' : '' ?>>Viuvo(a)</option>
                        <option value="outro" <?= $estadoCivil === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-telephone"></i> Contato e endereco</h3>
            <div class="crud-form-grid">
                <div class="crud-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" placeholder="membro@email.com">
                </div>
                <div class="crud-field">
                    <label for="telefone">Telefone / WhatsApp</label>
                    <input type="tel" id="telefone" name="telefone" value="<?= htmlspecialchars($telefone, ENT_QUOTES, 'UTF-8') ?>" placeholder="(00) 00000-0000">
                </div>
                <div class="crud-field crud-field-full">
                    <label for="endereco">Endereco</label>
                    <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($endereco, ENT_QUOTES, 'UTF-8') ?>" placeholder="Rua, numero, bairro">
                </div>
                <div class="crud-field">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($cidade, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cidade">
                </div>
                <div class="crud-field">
                    <label for="estado">UF</label>
                    <input type="text" id="estado" name="estado" value="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>" placeholder="SP" maxlength="2" style="text-transform: uppercase;">
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-shield-check"></i> Membresia</h3>
            <div class="crud-form-grid">
                <div class="crud-field">
                    <label for="data_membresia">Membro desde</label>
                    <input type="date" id="data_membresia" name="data_membresia" value="<?= htmlspecialchars($dataMembresia, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= $status === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="observacoes">Observacoes</label>
                    <textarea id="observacoes" name="observacoes" rows="4" placeholder="Anotacoes sobre o membro (opcional)"><?= htmlspecialchars($observacoes, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/membros" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alteracoes' : 'Cadastrar membro' ?>
            </button>
        </div>
    </form>
</div>
