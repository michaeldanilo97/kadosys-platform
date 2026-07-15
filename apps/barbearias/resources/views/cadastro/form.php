<?php

use Barbearias\Core\View;
use Barbearias\Models\Plano;

/**
 * @var array $config
 * @var string $csrf
 * @var array $errors
 * @var array $old
 * @var array<string, float> $planos
 * @var int $trialDias
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="cadastro-shell">
    <div class="glass-card cadastro-card">
        <h1>Criar minha conta</h1>
        <p class="subtitle">Cadastre sua barbearia, escolha um plano e comece a usar hoje mesmo.</p>

        <?php if ($errors !== []): ?>
            <div class="form-alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/cadastro" novalidate data-cadastro-form>
            <?= $csrf ?>

            <div class="form-field">
                <label for="nome">Nome da barbearia</label>
                <input
                    type="text"
                    id="nome"
                    name="nome"
                    value="<?= htmlspecialchars($old['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Barbearia do Zé"
                    autocomplete="organization"
                    data-cadastro-nome
                    required
                    autofocus
                >
            </div>

            <div class="form-field">
                <label for="slug">Identificador</label>
                <div class="slug-input">
                    <input
                        type="text"
                        id="slug"
                        name="slug"
                        value="<?= htmlspecialchars($old['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="barbearia-do-ze"
                        autocomplete="off"
                        data-cadastro-slug
                        required
                    >
                    <span class="slug-suffix">kadosys</span>
                </div>
                <span class="form-field-hint">Sugerido automaticamente pelo nome - pode editar se preferir.</span>
            </div>

            <div class="form-field-row">
                <div class="form-field">
                    <label for="admin_nome">Seu nome</label>
                    <input
                        type="text"
                        id="admin_nome"
                        name="admin_nome"
                        value="<?= htmlspecialchars($old['admin_nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Nome completo"
                        autocomplete="name"
                        required
                    >
                </div>
                <div class="form-field">
                    <label for="admin_email">Seu e-mail</label>
                    <input
                        type="email"
                        id="admin_email"
                        name="admin_email"
                        value="<?= htmlspecialchars($old['admin_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="voce@email.com"
                        autocomplete="email"
                        required
                    >
                </div>
            </div>

            <div class="form-field">
                <label for="telefone">Telefone (opcional)</label>
                <input
                    type="text"
                    id="telefone"
                    name="telefone"
                    value="<?= htmlspecialchars($old['telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="(11) 90000-0000"
                    autocomplete="tel"
                >
            </div>

            <div class="form-field">
                <label>CPF ou CNPJ</label>
                <div class="escolha-grid">
                    <label class="escolha-card">
                        <input type="radio" name="documento_tipo" value="cpf" data-documento-tipo <?= ($old['documento_tipo'] ?? 'cpf') === 'cpf' ? 'checked' : '' ?>>
                        <span class="nome">CPF</span>
                        <span class="desc">Pessoa física</span>
                    </label>
                    <label class="escolha-card">
                        <input type="radio" name="documento_tipo" value="cnpj" data-documento-tipo <?= ($old['documento_tipo'] ?? '') === 'cnpj' ? 'checked' : '' ?>>
                        <span class="nome">CNPJ</span>
                        <span class="desc">Pessoa jurídica</span>
                    </label>
                </div>
            </div>

            <div class="form-field-row">
                <div class="form-field">
                    <label for="documento" data-documento-label>CPF</label>
                    <input
                        type="text"
                        id="documento"
                        name="documento"
                        value="<?= htmlspecialchars($old['documento'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="000.000.000-00"
                        inputmode="numeric"
                        autocomplete="off"
                        data-documento-input
                        required
                    >
                    <span class="form-field-hint">Usado só pra evitar abuso do teste grátis.</span>
                </div>
                <div class="form-field" data-razao-social-field hidden>
                    <label for="razao_social">Razão social</label>
                    <input
                        type="text"
                        id="razao_social"
                        name="razao_social"
                        value="<?= htmlspecialchars($old['razao_social'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Razão social"
                        autocomplete="organization"
                    >
                </div>
            </div>

            <div class="form-field-row">
                <div class="form-field">
                    <label for="senha">Senha</label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Mínimo 8 caracteres"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                </div>
                <div class="form-field">
                    <label for="senha_confirmacao">Confirmar senha</label>
                    <input
                        type="password"
                        id="senha_confirmacao"
                        name="senha_confirmacao"
                        placeholder="Repita a senha"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                </div>
            </div>

            <div class="form-field">
                <label>Escolha o plano</label>
                <div class="escolha-grid">
                    <?php foreach ($planos as $valor => $preco): ?>
                        <label class="escolha-card">
                            <input
                                type="radio"
                                name="plano"
                                value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>"
                                <?= ($old['plano'] ?? Plano::ESSENCIAL) === $valor ? 'checked' : '' ?>
                                required
                            >
                            <span class="nome"><?= htmlspecialchars(Plano::label($valor), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="preco">R$ <?= number_format($preco, 2, ',', '.') ?><small>/mês</small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-field">
                <label>Forma de pagamento</label>
                <div class="escolha-grid">
                    <label class="escolha-card">
                        <input type="radio" name="metodo_pagamento" value="cartao" <?= ($old['metodo_pagamento'] ?? 'cartao') === 'cartao' ? 'checked' : '' ?>>
                        <span class="nome">💳 Cartão</span>
                        <span class="desc">Cobrança automática todo mês</span>
                    </label>
                    <label class="escolha-card">
                        <input type="radio" name="metodo_pagamento" value="pix" <?= ($old['metodo_pagamento'] ?? '') === 'pix' ? 'checked' : '' ?>>
                        <span class="nome">🔳 Pix</span>
                        <span class="desc">Fatura nova todo mês, paga na hora</span>
                    </label>
                    <label class="escolha-card">
                        <input type="radio" name="metodo_pagamento" value="trial" <?= ($old['metodo_pagamento'] ?? '') === 'trial' ? 'checked' : '' ?>>
                        <span class="nome">🎁 Teste grátis</span>
                        <span class="desc"><?= (int) $trialDias ?> dias grátis, sem cartão</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-k btn-k-grad" style="width:100%; margin-top: 0.5rem;">Criar conta</button>
        </form>

        <p style="text-align:center; margin-top:1.5rem; font-size:0.85rem; color: var(--gray-400);">
            Já tem uma conta? <a href="<?= $basePath ?>/login">Entrar</a>
        </p>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/cadastro.js?v=<?= View::assetVersion('assets/js/cadastro.js') ?>"></script>
