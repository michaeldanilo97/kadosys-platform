<?php
/**
 * @var array $config
 */
$basePath = $config['base_path'] ?? '';
?>
<div class="auth-form-card">
    <div class="eyebrow">Quase la</div>
    <h1>Recebemos seu pedido!</h1>
    <p class="subtitle">
        Seu pagamento esta sendo processado pelo Mercado Pago. Assim que for aprovado,
        preparamos o acesso da sua igreja automaticamente e enviamos um e-mail com o
        endereco e os proximos passos para entrar pela primeira vez.
    </p>
    <p class="subtitle">Isso costuma levar poucos minutos.</p>

    <a href="<?= $basePath ?>/" class="btn-k btn-k-grad">Voltar para o inicio</a>
</div>
