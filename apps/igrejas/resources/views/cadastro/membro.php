<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var string|null $nomeIgreja
 * @var bool $habilitado
 * @var bool $sucesso
 * @var string $csrf
 * @var array $errors
 * @var array $old
 */
$basePath = $config['base_path'] ?? '';
$genero = $old['genero'] ?? '';
$estadoCivil = $old['estado_civil'] ?? '';
?>
<div class="auth-form-card auth-form-card-wide">
    <div class="eyebrow"><?= htmlspecialchars($nomeIgreja ?? 'Nossa igreja', ENT_QUOTES, 'UTF-8') ?></div>
    <h1>Cadastro de membro</h1>

    <?php if (!$habilitado): ?>
        <p class="subtitle">O auto-cadastro não está disponível no momento. Fale com a secretaria da igreja pra ser cadastrado.</p>
    <?php elseif ($sucesso): ?>
        <div class="auth-alert" style="background: rgba(52,211,153,0.12); border-color: rgba(52,211,153,0.35); color: var(--success);">
            <i class="bi bi-check-circle"></i> Cadastro realizado com sucesso! Seja bem-vindo(a).
        </div>
    <?php else: ?>
        <p class="subtitle">Preencha seus dados para se cadastrar como membro. Seu e-mail e senha viram seu login de acesso.</p>

        <?php if ($errors !== []): ?>
            <div class="auth-alert error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/cadastro" novalidate>
            <?= $csrf ?>

            <div class="auth-field">
                <label for="nome">Nome completo</label>
                <input
                    type="text"
                    class="form-control"
                    id="nome"
                    name="nome"
                    value="<?= htmlspecialchars($old['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Seu nome completo"
                    autocomplete="name"
                    required
                    autofocus
                >
            </div>

            <div class="auth-field-row">
                <div class="auth-field">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="voce@email.com"
                        autocomplete="email"
                        required
                    >
                </div>
                <div class="auth-field">
                    <label for="telefone">Telefone</label>
                    <input
                        type="text"
                        class="form-control"
                        id="telefone"
                        name="telefone"
                        value="<?= htmlspecialchars($old['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="(00) 00000-0000"
                        autocomplete="tel"
                    >
                </div>
            </div>

            <div class="auth-field-row">
                <div class="auth-field">
                    <label for="data_nascimento">Data de nascimento</label>
                    <input
                        type="date"
                        class="form-control"
                        id="data_nascimento"
                        name="data_nascimento"
                        value="<?= htmlspecialchars($old['data_nascimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="bday"
                    >
                </div>
                <div class="auth-field">
                    <label for="genero">Gênero</label>
                    <select id="genero" name="genero" class="form-control">
                        <option value="" <?= $genero === '' ? 'selected' : '' ?>>Não informado</option>
                        <option value="feminino" <?= $genero === 'feminino' ? 'selected' : '' ?>>Feminino</option>
                        <option value="masculino" <?= $genero === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                        <option value="outro" <?= $genero === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
            </div>

            <div class="auth-field">
                <label for="estado_civil">Estado civil</label>
                <select id="estado_civil" name="estado_civil" class="form-control">
                    <option value="" <?= $estadoCivil === '' ? 'selected' : '' ?>>Não informado</option>
                    <option value="solteiro" <?= $estadoCivil === 'solteiro' ? 'selected' : '' ?>>Solteiro(a)</option>
                    <option value="casado" <?= $estadoCivil === 'casado' ? 'selected' : '' ?>>Casado(a)</option>
                    <option value="divorciado" <?= $estadoCivil === 'divorciado' ? 'selected' : '' ?>>Divorciado(a)</option>
                    <option value="viuvo" <?= $estadoCivil === 'viuvo' ? 'selected' : '' ?>>Viúvo(a)</option>
                    <option value="outro" <?= $estadoCivil === 'outro' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>

            <div class="auth-field-row">
                <div class="auth-field">
                    <label for="cep">CEP</label>
                    <input
                        type="text"
                        class="form-control"
                        id="cep"
                        name="cep"
                        value="<?= htmlspecialchars($old['cep'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="00000-000"
                        inputmode="numeric"
                        autocomplete="postal-code"
                        data-cep-input
                        data-cep-endereco="endereco"
                        data-cep-cidade="cidade"
                        data-cep-estado="estado"
                    >
                    <span class="auth-field-hint" data-cep-status></span>
                </div>
                <div class="auth-field">
                    <label for="estado">Estado</label>
                    <input
                        type="text"
                        class="form-control"
                        id="estado"
                        name="estado"
                        value="<?= htmlspecialchars($old['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="UF"
                        maxlength="2"
                        autocomplete="address-level1"
                    >
                </div>
            </div>

            <div class="auth-field-row">
                <div class="auth-field">
                    <label for="endereco">Endereço</label>
                    <input
                        type="text"
                        class="form-control"
                        id="endereco"
                        name="endereco"
                        value="<?= htmlspecialchars($old['endereco'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Rua, número, bairro"
                        autocomplete="street-address"
                    >
                </div>
                <div class="auth-field">
                    <label for="cidade">Cidade</label>
                    <input
                        type="text"
                        class="form-control"
                        id="cidade"
                        name="cidade"
                        value="<?= htmlspecialchars($old['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Sua cidade"
                        autocomplete="address-level2"
                    >
                </div>
            </div>

            <div class="auth-field-row">
                <div class="auth-field">
                    <label for="senha">Senha</label>
                    <input
                        type="password"
                        class="form-control"
                        id="senha"
                        name="senha"
                        placeholder="Mínimo 8 caracteres"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                </div>
                <div class="auth-field">
                    <label for="senha_confirmacao">Confirmar senha</label>
                    <input
                        type="password"
                        class="form-control"
                        id="senha_confirmacao"
                        name="senha_confirmacao"
                        placeholder="Repita a senha"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                </div>
            </div>

            <button type="submit" class="btn-k btn-k-grad">Concluir cadastro <i class="bi bi-arrow-right"></i></button>
        </form>
    <?php endif; ?>

    <a href="<?= $basePath ?>/login" class="auth-back-link">
        <i class="bi bi-arrow-left"></i> Já sou cadastrado - entrar
    </a>
</div>

<?php if ($habilitado && !$sucesso): ?>
    <script src="<?= $basePath ?>/assets/js/cep-autofill.js?v=<?= View::assetVersion('assets/js/cep-autofill.js') ?>"></script>
<?php endif; ?>
