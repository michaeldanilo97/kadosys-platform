<?php

use Igrejas\Core\View;
use Igrejas\Models\Plano;

/**
 * @var array $config
 * @var string $csrf
 * @var array $errors
 * @var array $old
 * @var array<string, float> $planos
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="auth-form-card auth-form-card-wide">
    <div class="eyebrow">Comece agora</div>
    <h1>Criar minha conta</h1>
    <p class="subtitle">Cadastre sua igreja, escolha um plano e comece a usar o KADOSYS hoje mesmo.</p>

    <?php if ($errors !== []): ?>
        <div class="auth-alert error">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/cadastro" novalidate data-cadastro-form>
        <?= $csrf ?>

        <div class="auth-field">
            <label for="nome_igreja">Nome da igreja</label>
            <input
                type="text"
                class="form-control"
                id="nome_igreja"
                name="nome_igreja"
                value="<?= htmlspecialchars($old['nome_igreja'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Igreja Batista Central"
                autocomplete="organization"
                data-cadastro-nome-igreja
                required
                autofocus
            >
        </div>

        <div class="auth-field">
            <label for="slug">URL de acesso ao painel</label>
            <div class="auth-slug-input">
                <input
                    type="text"
                    class="form-control"
                    id="slug"
                    name="slug"
                    value="<?= htmlspecialchars($old['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="igreja-batista-central"
                    autocomplete="off"
                    data-cadastro-slug
                    required
                >
                <span class="auth-slug-suffix">.kadosys.com.br</span>
            </div>
            <span class="auth-field-hint">Sugerido automaticamente pelo nome da igreja - pode editar se preferir.</span>
        </div>

        <div class="auth-field-row">
            <div class="auth-field">
                <label for="admin_nome">Seu nome</label>
                <input
                    type="text"
                    class="form-control"
                    id="admin_nome"
                    name="admin_nome"
                    value="<?= htmlspecialchars($old['admin_nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Nome completo"
                    autocomplete="name"
                    required
                >
            </div>
            <div class="auth-field">
                <label for="admin_email">Seu e-mail</label>
                <input
                    type="email"
                    class="form-control"
                    id="admin_email"
                    name="admin_email"
                    value="<?= htmlspecialchars($old['admin_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="voce@email.com"
                    autocomplete="email"
                    required
                >
            </div>
        </div>

        <div class="auth-field">
            <label>CPF ou CNPJ</label>
            <div class="plano-escolha">
                <label class="plano-escolha-card" data-plano-card>
                    <input
                        type="radio"
                        name="documento_tipo"
                        value="cpf"
                        data-documento-tipo
                        <?= ($old['documento_tipo'] ?? 'cpf') === 'cpf' ? 'checked' : '' ?>
                    >
                    <span class="nome">CPF</span>
                    <span class="desc">Pessoa fisica</span>
                </label>
                <label class="plano-escolha-card" data-plano-card>
                    <input
                        type="radio"
                        name="documento_tipo"
                        value="cnpj"
                        data-documento-tipo
                        <?= ($old['documento_tipo'] ?? '') === 'cnpj' ? 'checked' : '' ?>
                    >
                    <span class="nome">CNPJ</span>
                    <span class="desc">Pessoa juridica</span>
                </label>
            </div>
        </div>

        <div class="auth-field-row">
            <div class="auth-field">
                <label for="documento" data-documento-label>CPF</label>
                <input
                    type="text"
                    class="form-control"
                    id="documento"
                    name="documento"
                    value="<?= htmlspecialchars($old['documento'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="000.000.000-00"
                    inputmode="numeric"
                    autocomplete="off"
                    data-documento-input
                    required
                >
                <span class="auth-field-hint">Usado so para evitar abuso do teste gratis - seus dados ficam seguros.</span>
            </div>
            <div class="auth-field" data-razao-social-field hidden>
                <label for="razao_social">Razao social</label>
                <input
                    type="text"
                    class="form-control"
                    id="razao_social"
                    name="razao_social"
                    value="<?= htmlspecialchars($old['razao_social'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Razao social da igreja/instituicao"
                    autocomplete="organization"
                    data-razao-social-input
                >
            </div>
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
                    maxlength="9"
                    data-cep-input
                    data-cep-endereco="endereco"
                    data-cep-cidade="cidade"
                    data-cep-estado="estado"
                >
                <span class="auth-field-hint" data-cep-status></span>
            </div>
            <div class="auth-field">
                <label for="numero">Numero</label>
                <input
                    type="text"
                    class="form-control"
                    id="numero"
                    name="numero"
                    value="<?= htmlspecialchars($old['numero'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="123"
                    autocomplete="address-line2"
                >
            </div>
        </div>

        <div class="auth-field">
            <label for="endereco">Endereco</label>
            <input
                type="text"
                class="form-control"
                id="endereco"
                name="endereco"
                value="<?= htmlspecialchars($old['endereco'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Rua, bairro"
                autocomplete="address-line1"
            >
        </div>

        <div class="auth-field-row">
            <div class="auth-field">
                <label for="cidade">Cidade</label>
                <input
                    type="text"
                    class="form-control"
                    id="cidade"
                    name="cidade"
                    value="<?= htmlspecialchars($old['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Cidade"
                    autocomplete="address-level2"
                >
            </div>
            <div class="auth-field">
                <label for="estado">Estado</label>
                <input
                    type="text"
                    class="form-control"
                    id="estado"
                    name="estado"
                    value="<?= htmlspecialchars($old['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="SP"
                    maxlength="2"
                    style="text-transform: uppercase;"
                    autocomplete="address-level1"
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
                    placeholder="Minimo 8 caracteres"
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

        <div class="auth-field">
            <label>Escolha o plano</label>
            <div class="plano-escolha">
                <?php foreach ($planos as $valor => $preco): ?>
                    <label class="plano-escolha-card" data-plano-card>
                        <input
                            type="radio"
                            name="plano"
                            value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>"
                            <?= ($old['plano'] ?? Plano::ESSENCIAL) === $valor ? 'checked' : '' ?>
                            required
                        >
                        <span class="nome"><?= htmlspecialchars(Plano::label($valor), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="preco">R$ <?= number_format($preco, 2, ',', '.') ?><small>/mes</small></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <span class="auth-field-hint">Sua igreja e uma rede com necessidades especiais? <a href="mailto:contato@kadosys.com.br">Fale com a gente</a>.</span>
        </div>

        <div class="auth-field">
            <label>Forma de pagamento</label>
            <div class="plano-escolha">
                <label class="plano-escolha-card" data-plano-card>
                    <input
                        type="radio"
                        name="metodo_pagamento"
                        value="cartao"
                        <?= ($old['metodo_pagamento'] ?? 'cartao') === 'cartao' ? 'checked' : '' ?>
                    >
                    <span class="nome"><i class="bi bi-credit-card"></i> Cartao</span>
                    <span class="desc">Cobranca automatica todo mes</span>
                </label>
                <label class="plano-escolha-card" data-plano-card>
                    <input
                        type="radio"
                        name="metodo_pagamento"
                        value="pix"
                        <?= ($old['metodo_pagamento'] ?? '') === 'pix' ? 'checked' : '' ?>
                    >
                    <span class="nome"><i class="bi bi-qr-code"></i> Pix</span>
                    <span class="desc">Fatura nova todo mes, paga na hora</span>
                </label>
                <label class="plano-escolha-card" data-plano-card>
                    <input
                        type="radio"
                        name="metodo_pagamento"
                        value="trial"
                        <?= ($old['metodo_pagamento'] ?? '') === 'trial' ? 'checked' : '' ?>
                    >
                    <span class="nome"><i class="bi bi-gift"></i> Teste gratis</span>
                    <span class="desc">7 dias gratis, sem cartao</span>
                </label>
            </div>
        </div>

        <button type="submit" class="btn-k btn-k-grad" data-cadastro-submit>Criar conta e ir para o pagamento <i class="bi bi-arrow-right"></i></button>
    </form>

    <a href="<?= $basePath ?>/login" class="auth-back-link">
        <i class="bi bi-arrow-left"></i> Ja tenho uma conta - entrar
    </a>
</div>

<script src="<?= $basePath ?>/assets/js/cadastro.js?v=<?= View::assetVersion('assets/js/cadastro.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/cep-autofill.js?v=<?= View::assetVersion('assets/js/cep-autofill.js') ?>"></script>
