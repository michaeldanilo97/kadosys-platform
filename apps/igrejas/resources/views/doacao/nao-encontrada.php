<?php
/** @var array $config */
$basePath = $config['base_path'] ?? '';
?>
<div class="auth-form-card">
    <div class="eyebrow">Doação</div>
    <h1>Link não encontrado</h1>
    <p class="subtitle">Esse link de doação não existe mais ou o endereço está incorreto.</p>

    <a href="<?= $basePath ?>/doar" class="btn-k btn-k-grad">
        <i class="bi bi-arrow-left"></i> Fazer uma doação
    </a>
</div>
