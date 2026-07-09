<?php
/** @var array $config */
$basePath = $config['base_path'] ?? '';
?>
<div class="auth-form-card">
    <div class="eyebrow">Doacao</div>
    <h1>Link nao encontrado</h1>
    <p class="subtitle">Esse link de doacao nao existe mais ou o endereco esta incorreto.</p>

    <a href="<?= $basePath ?>/doar" class="btn-k btn-k-grad">
        <i class="bi bi-arrow-left"></i> Fazer uma doacao
    </a>
</div>
