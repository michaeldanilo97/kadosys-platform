<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var string $subdominio
 */
$basePath = $config['base_path'] ?? '';
$loginUrl = 'https://' . $subdominio . '/login';
?>
<div class="auth-form-card">
    <div class="eyebrow">Quase la</div>
    <h1>Estamos preparando tudo</h1>
    <p class="subtitle">
        Sua igreja foi criada com sucesso! Estamos finalizando a configuracao do endereco
        <strong><?= htmlspecialchars($subdominio, ENT_QUOTES, 'UTF-8') ?></strong> - na primeira vez isso
        pode levar ate um minuto.
    </p>

    <div class="provisionamento-status" data-provisionamento-status>
        <i class="bi bi-hourglass-split spin"></i> Preparando o ambiente da sua igreja...
    </div>

    <p class="auth-signup-hint" data-provisionamento-fallback hidden>
        Esta demorando um pouco mais que o normal. Voce pode aguardar mais um instante ou
        <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">tentar entrar agora</a>.
    </p>
</div>

<script>
  window.KADOSYS_LOGIN_URL = <?= json_encode($loginUrl, JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= $basePath ?>/assets/js/cadastro-pronto.js?v=<?= View::assetVersion('assets/js/cadastro-pronto.js') ?>"></script>
