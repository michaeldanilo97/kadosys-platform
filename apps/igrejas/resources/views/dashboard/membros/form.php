<?php

use Igrejas\Core\View;

/**
 * Cadastro de um novo membro - a edicao de um membro existente
 * acontece na propria tela de perfil (dashboard.membros.show), nao
 * mais aqui.
 *
 * @var array $config
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';

$nome = $old['nome'] ?? '';
$email = $old['email'] ?? '';
$telefone = $old['telefone'] ?? '';
$dataNascimento = $old['data_nascimento'] ?? '';
$genero = $old['genero'] ?? '';
$estadoCivil = $old['estado_civil'] ?? '';
$endereco = $old['endereco'] ?? '';
$cidade = $old['cidade'] ?? '';
$estado = $old['estado'] ?? '';
$dataMembresia = $old['data_membresia'] ?? '';
$status = $old['status'] ?? 'ativo';
$observacoes = $old['observacoes'] ?? '';
$criarAcesso = !empty($old['criar_acesso']);
$acessoMusico = !empty($old['musico']);
$acessoLiderLouvor = !empty($old['lider_louvor']);

$actionUrl = $basePath . '/dashboard/membros';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Novo membro</h1>
        <p class="dash-page-subtitle">Preencha os dados para cadastrar um novo membro.</p>
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
                    <label for="genero">Gênero</label>
                    <select id="genero" name="genero">
                        <option value="" <?= $genero === '' ? 'selected' : '' ?>>Não informado</option>
                        <option value="feminino" <?= $genero === 'feminino' ? 'selected' : '' ?>>Feminino</option>
                        <option value="masculino" <?= $genero === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                        <option value="outro" <?= $genero === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="estado_civil">Estado civil</label>
                    <select id="estado_civil" name="estado_civil">
                        <option value="" <?= $estadoCivil === '' ? 'selected' : '' ?>>Não informado</option>
                        <option value="solteiro" <?= $estadoCivil === 'solteiro' ? 'selected' : '' ?>>Solteiro(a)</option>
                        <option value="casado" <?= $estadoCivil === 'casado' ? 'selected' : '' ?>>Casado(a)</option>
                        <option value="divorciado" <?= $estadoCivil === 'divorciado' ? 'selected' : '' ?>>Divorciado(a)</option>
                        <option value="viuvo" <?= $estadoCivil === 'viuvo' ? 'selected' : '' ?>>Viúvo(a)</option>
                        <option value="outro" <?= $estadoCivil === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-telephone"></i> Contato e endereço</h3>
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
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($endereco, ENT_QUOTES, 'UTF-8') ?>" placeholder="Rua, número, bairro">
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
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="4" placeholder="Anotações sobre o membro (opcional)"><?= htmlspecialchars($observacoes, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </div>

        <div class="crud-form-section">
                <h3><i class="bi bi-key"></i> Acesso ao sistema</h3>
                <div class="crud-form-grid">
                    <div class="crud-field crud-field-full">
                        <label class="toggle-switch-field">
                            <input type="checkbox" name="criar_acesso" value="1" data-criar-acesso <?= $criarAcesso ? 'checked' : '' ?>>
                            <span class="toggle-switch"></span>
                            <span class="toggle-switch-label">
                                Criar acesso ao sistema para este membro
                                <span class="auth-field-hint">Cria um login (usando o e-mail acima) já com o cargo escolhido, sem precisar cadastrar de novo em Usuários depois.</span>
                            </span>
                        </label>
                    </div>
                    <div class="crud-field" data-acesso-campo <?= $criarAcesso ? '' : 'hidden' ?>>
                        <label for="senha">Senha *</label>
                        <input type="password" id="senha" name="senha" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                    </div>
                    <div class="crud-field" data-acesso-campo <?= $criarAcesso ? '' : 'hidden' ?>>
                        <label for="senha_confirmacao">Confirmar senha *</label>
                        <input type="password" id="senha_confirmacao" name="senha_confirmacao" placeholder="Repita a senha" autocomplete="new-password">
                    </div>
                    <div class="crud-field crud-field-full" data-acesso-campo <?= $criarAcesso ? '' : 'hidden' ?>>
                        <label class="toggle-switch-field">
                            <input type="checkbox" name="musico" value="1" <?= $acessoMusico ? 'checked' : '' ?>>
                            <span class="toggle-switch"></span>
                            <span class="toggle-switch-label">
                                Músico
                                <span class="auth-field-hint">Libera automaticamente o acesso ao módulo Louvores (letras, cifras e tons).</span>
                            </span>
                        </label>
                    </div>
                    <div class="crud-field crud-field-full" data-acesso-campo <?= $criarAcesso ? '' : 'hidden' ?>>
                        <label class="toggle-switch-field">
                            <input type="checkbox" name="lider_louvor" value="1" <?= $acessoLiderLouvor ? 'checked' : '' ?>>
                            <span class="toggle-switch"></span>
                            <span class="toggle-switch-label">
                                Líder de louvor
                                <span class="auth-field-hint">Além do acesso de músico, pode montar/reordenar a programação de culto e avançar a música atual no Modo Culto.</span>
                            </span>
                        </label>
                    </div>
                </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/membros" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> Cadastrar membro
            </button>
        </div>
    </form>
</div>

<script src="<?= $basePath ?>/assets/js/membro-form.js?v=<?= View::assetVersion('assets/js/membro-form.js') ?>"></script>
