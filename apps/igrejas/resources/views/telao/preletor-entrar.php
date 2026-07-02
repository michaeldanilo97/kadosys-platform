<?php
/**
 * @var array $config
 * @var string|null $error
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="preletor-entrada">
    <div class="preletor-card">
        <div class="glyph"><i class="bi bi-tablet"></i></div>
        <h1>Acesso do preletor</h1>
        <p>Digite o PIN de 6 digitos informado pelo operador da projecao.</p>

        <?php if (!empty($error)): ?>
            <div class="preletor-erro"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/preletor">
            <input
                type="text"
                name="pin"
                class="preletor-pin-input"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                placeholder="000000"
                autofocus
                required
            >
            <button type="submit" class="preletor-btn">Entrar</button>
        </form>
    </div>
</div>
