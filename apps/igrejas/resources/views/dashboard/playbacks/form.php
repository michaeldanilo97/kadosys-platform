<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var \Igrejas\Models\Playback|null $playback
 * @var array $old
 * @var array $errors
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$isEdit = $playback !== null;

$titulo = $old['titulo'] ?? $playback->titulo ?? '';
$artista = $old['artista'] ?? $playback->artista ?? '';
$status = $old['status'] ?? $playback->status ?? 'ativo';

$actionUrl = $isEdit
    ? $basePath . '/dashboard/playbacks/' . $playback->id
    : $basePath . '/dashboard/playbacks';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= $isEdit ? 'Editar playback' : 'Novo playback' ?></h1>
        <p class="dash-page-subtitle">
            <?= $isEdit
                ? 'Atualize o título e o artista de ' . htmlspecialchars($playback->titulo, ENT_QUOTES, 'UTF-8') . '.'
                : 'Envie um arquivo de áudio (MP3, WAV, OGG, M4A ou AAC, até 25MB).' ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/playbacks" class="btn-k btn-k-ghost">
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
    <form method="POST" action="<?= $actionUrl ?>" class="crud-form" enctype="multipart/form-data">
        <?= $csrf ?>

        <div class="crud-form-section">
            <h3><i class="bi bi-music-note-beamed"></i> Dados do playback</h3>
            <div class="crud-form-grid">
                <div class="crud-field crud-field-full">
                    <label for="titulo">Título *</label>
                    <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Reconhece Tua Grandeza" required autofocus>
                </div>
                <div class="crud-field crud-field-full">
                    <label for="artista">Artista / intérprete</label>
                    <input type="text" id="artista" name="artista" value="<?= htmlspecialchars($artista, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Ministério Morada (opcional)">
                </div>

                <?php if (!$isEdit): ?>
                    <div class="crud-field crud-field-full">
                        <label for="audio">Arquivo de áudio *</label>
                        <input type="file" id="audio" name="audio" accept="audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/mp4,audio/x-m4a,audio/aac,.mp3,.wav,.ogg,.m4a,.aac" required>
                    </div>
                <?php else: ?>
                    <div class="crud-field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="inativo" <?= $status === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="crud-form-actions">
            <a href="<?= $basePath ?>/dashboard/playbacks" class="btn-k btn-k-ghost">Cancelar</a>
            <button type="submit" class="btn-k btn-k-grad">
                <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Salvar alterações' : 'Enviar playback' ?>
            </button>
        </div>
    </form>
</div>
