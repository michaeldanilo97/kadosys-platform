<?php

use Igrejas\Core\View;
use Igrejas\Models\Membro;
use Igrejas\Models\User;

/**
 * @var array $config
 * @var User $user
 * @var Membro $membro
 * @var string|null $success
 * @var array $errors
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Meu perfil</h1>
        <p class="dash-page-subtitle">Foto, cargo, dados pessoais e endereço - o que aparece na tela Equipe e o que a secretaria vê no seu cadastro em Membros.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/equipe" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

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
    <form method="POST" action="<?= $basePath ?>/dashboard/perfil" class="crud-form" enctype="multipart/form-data">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="crud-form-section">
            <h3><i class="bi bi-person-circle"></i> Foto</h3>
            <div class="perfil-foto-atual">
                <?php if ($user->fotoPath !== null): ?>
                    <img src="<?= $basePath ?>/<?= htmlspecialchars($user->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Foto atual">
                <?php else: ?>
                    <span class="equipe-card-inicial"><?= htmlspecialchars(mb_strtoupper(mb_substr($user->name, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <div class="crud-field">
                    <label for="foto">Trocar foto</label>
                    <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp">
                    <span class="auth-field-hint">PNG, JPG ou WEBP, até 5MB.</span>
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-music-note-list"></i> Cargo na equipe</h3>
            <div class="crud-form-grid">
                <div class="crud-field">
                    <label for="cargo">Cargo</label>
                    <select id="cargo" name="cargo" data-cargo-select>
                        <?php foreach (User::CARGOS as $cargoSlug => $cargoInfo): ?>
                            <option value="<?= $cargoSlug ?>" <?= $user->cargo === $cargoSlug ? 'selected' : '' ?>><?= htmlspecialchars($cargoInfo['label'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="crud-field" data-instrumento-field <?= $user->cargo !== User::CARGO_MUSICO ? 'hidden' : '' ?>>
                    <label for="instrumento">Instrumento</label>
                    <select id="instrumento" name="instrumento">
                        <option value="">Nenhum</option>
                        <?php foreach (User::INSTRUMENTOS as $instrumentoSlug => $instrumentoInfo): ?>
                            <option value="<?= $instrumentoSlug ?>" <?= $user->instrumento === $instrumentoSlug ? 'selected' : '' ?>><?= $instrumentoInfo['emoji'] ?> <?= htmlspecialchars($instrumentoInfo['label'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-person-vcard"></i> Dados pessoais</h3>
            <div class="crud-form-grid">
                <div class="crud-field">
                    <label for="telefone">Telefone / WhatsApp</label>
                    <input type="tel" id="telefone" name="telefone" value="<?= htmlspecialchars((string) $membro->telefone, ENT_QUOTES, 'UTF-8') ?>" placeholder="(00) 00000-0000">
                </div>
                <div class="crud-field">
                    <label for="data_nascimento">Data de nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars((string) $membro->dataNascimento, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="genero">Sexo</label>
                    <select id="genero" name="genero">
                        <option value="" <?= $membro->genero === null ? 'selected' : '' ?>>Não informado</option>
                        <option value="feminino" <?= $membro->genero === 'feminino' ? 'selected' : '' ?>>Feminino</option>
                        <option value="masculino" <?= $membro->genero === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                        <option value="outro" <?= $membro->genero === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="estado_civil">Estado civil</label>
                    <select id="estado_civil" name="estado_civil">
                        <option value="" <?= $membro->estadoCivil === null ? 'selected' : '' ?>>Não informado</option>
                        <option value="solteiro" <?= $membro->estadoCivil === 'solteiro' ? 'selected' : '' ?>>Solteiro(a)</option>
                        <option value="casado" <?= $membro->estadoCivil === 'casado' ? 'selected' : '' ?>>Casado(a)</option>
                        <option value="divorciado" <?= $membro->estadoCivil === 'divorciado' ? 'selected' : '' ?>>Divorciado(a)</option>
                        <option value="viuvo" <?= $membro->estadoCivil === 'viuvo' ? 'selected' : '' ?>>Viúvo(a)</option>
                        <option value="outro" <?= $membro->estadoCivil === 'outro' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="crud-field">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars((string) $membro->cpf, ENT_QUOTES, 'UTF-8') ?>" placeholder="000.000.000-00">
                </div>
                <div class="crud-field">
                    <label for="rg">RG</label>
                    <input type="text" id="rg" name="rg" value="<?= htmlspecialchars((string) $membro->rg, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="naturalidade">Naturalidade</label>
                    <input type="text" id="naturalidade" name="naturalidade" value="<?= htmlspecialchars((string) $membro->naturalidade, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cidade - UF">
                </div>
            </div>
        </div>

        <div class="crud-form-section">
            <h3><i class="bi bi-geo-alt"></i> Endereço</h3>
            <div class="crud-form-grid">
                <div class="crud-field">
                    <label for="cep">CEP</label>
                    <div class="member-cep-field">
                        <input
                            type="text" id="cep" name="cep"
                            value="<?= htmlspecialchars((string) $membro->cep, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="00000-000"
                            data-cep-input
                            data-cep-logradouro="logradouro"
                            data-cep-bairro="bairro"
                            data-cep-cidade="cidade"
                            data-cep-estado="estado"
                        >
                        <span class="member-cep-status" data-cep-status></span>
                    </div>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="logradouro">Logradouro</label>
                    <input type="text" id="logradouro" name="logradouro" value="<?= htmlspecialchars((string) $membro->logradouro, ENT_QUOTES, 'UTF-8') ?>" placeholder="Rua, avenida...">
                </div>
                <div class="crud-field">
                    <label for="numero">Número</label>
                    <input type="text" id="numero" name="numero" value="<?= htmlspecialchars((string) $membro->numero, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="complemento">Complemento</label>
                    <input type="text" id="complemento" name="complemento" value="<?= htmlspecialchars((string) $membro->complemento, ENT_QUOTES, 'UTF-8') ?>" placeholder="Apto, bloco...">
                </div>
                <div class="crud-field">
                    <label for="bairro">Bairro</label>
                    <input type="text" id="bairro" name="bairro" value="<?= htmlspecialchars((string) $membro->bairro, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars((string) $membro->cidade, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="crud-field">
                    <label for="estado">UF</label>
                    <input type="text" id="estado" name="estado" value="<?= htmlspecialchars((string) $membro->estado, ENT_QUOTES, 'UTF-8') ?>" maxlength="2" style="text-transform: uppercase;">
                </div>
            </div>
        </div>

        <div class="crud-form-actions">
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> Salvar perfil
            </button>
        </div>
    </form>
</div>

<script src="<?= $basePath ?>/assets/js/usuario-form.js?v=<?= View::assetVersion('assets/js/usuario-form.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/cep-autofill.js?v=<?= View::assetVersion('assets/js/cep-autofill.js') ?>"></script>
