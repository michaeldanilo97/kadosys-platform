<?php

/**
 * @var array $config
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Novo repertório</h1>
        <p class="dash-page-subtitle">Depois de criar, você adiciona e arrasta os louvores na ordem do culto.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/louvores/repertorios" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

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
    <form method="POST" action="<?= $basePath ?>/dashboard/louvores/repertorios" class="crud-form">
        <?= $csrf ?>
        <div class="crud-form-grid">
            <div class="crud-field crud-field-full">
                <label for="titulo">Título *</label>
                <input type="text" id="titulo" name="titulo" placeholder="Ex.: Culto de domingo - 13/07" required autofocus>
            </div>
        </div>
        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/louvores/repertorios" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> Criar e montar repertório
            </button>
        </div>
    </form>
</div>
