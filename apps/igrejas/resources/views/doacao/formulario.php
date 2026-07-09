<?php

/**
 * @var array $config
 * @var string|null $nomeIgreja
 * @var bool $habilitado
 * @var array<int, \Igrejas\Models\FinanceiroCategoria> $categorias
 * @var string $csrf
 * @var array $errors
 * @var array $old
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="auth-form-card">
    <div class="eyebrow"><?= htmlspecialchars($nomeIgreja ?? 'Nossa igreja', ENT_QUOTES, 'UTF-8') ?></div>
    <h1>Fazer uma doacao</h1>

    <?php if (!$habilitado): ?>
        <p class="subtitle">Esta igreja ainda nao configurou o recebimento de doacoes via Pix. Fale com a secretaria.</p>
    <?php else: ?>
        <p class="subtitle">Escolha o valor e a categoria - voce recebera um QR code Pix pra pagar direto pelo app do seu banco.</p>

        <?php if ($errors !== []): ?>
            <div class="auth-alert error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/doar" novalidate>
            <?= $csrf ?>

            <div class="auth-field">
                <label for="valor">Valor da doacao</label>
                <input
                    type="text"
                    class="form-control"
                    id="valor"
                    name="valor"
                    inputmode="decimal"
                    value="<?= htmlspecialchars($old['valor'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="R$ 0,00"
                    required
                    autofocus
                >
            </div>

            <?php if ($categorias !== []): ?>
                <div class="auth-field">
                    <label for="categoria_id">Categoria (opcional)</label>
                    <select id="categoria_id" name="categoria_id" class="form-control">
                        <option value="">Nao especificar</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria->id ?>" <?= (string) ($old['categoria_id'] ?? '') === (string) $categoria->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria->nome, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="auth-field">
                <label for="nome_doador">Seu nome (opcional)</label>
                <input
                    type="text"
                    class="form-control"
                    id="nome_doador"
                    name="nome_doador"
                    value="<?= htmlspecialchars($old['nome_doador'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Pode deixar em branco pra doar anonimamente"
                    autocomplete="name"
                >
            </div>

            <div class="auth-field">
                <label for="mensagem">Mensagem (opcional)</label>
                <input
                    type="text"
                    class="form-control"
                    id="mensagem"
                    name="mensagem"
                    value="<?= htmlspecialchars($old['mensagem'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Uma palavra, um pedido de oracao..."
                >
            </div>

            <button type="submit" class="btn-k btn-k-grad">Gerar QR code Pix <i class="bi bi-qr-code"></i></button>
        </form>
    <?php endif; ?>
</div>
