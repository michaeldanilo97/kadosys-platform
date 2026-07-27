<?php
/**
 * @var array $config
 * @var array<int, \Igrejas\Models\GaleriaMemoria> $memorias
 * @var array<int, string> $errors
 * @var string|null $success
 * @var array<string, string> $old
 * @var string $csrf
 * @var string $galeriaUrlAbsoluta
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Galeria de Memórias</h1>
        <p class="dash-page-subtitle">
            <?= count($memorias) ?> foto<?= count($memorias) === 1 ? '' : 's' ?> no mural.
        </p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <div class="auth-field">
        <label for="link_galeria">Link público para compartilhar</label>
        <div class="auth-slug-input">
            <input type="text" class="form-control" id="link_galeria" value="<?= htmlspecialchars($galeriaUrlAbsoluta, ENT_QUOTES, 'UTF-8') ?>" readonly>
            <button type="button" class="pix-copiar-btn" data-copiar-link="link_galeria" aria-label="Copiar link">
                <i class="bi bi-clipboard"></i>
            </button>
        </div>
        <span class="auth-field-hint">Compartilhe esse link com a congregação (grupo do WhatsApp, QR code no templo etc.).</span>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-head">
        <h2><i class="bi bi-images"></i> Fotos</h2>
    </div>

    <?php if ($errors !== []): ?>
        <div class="crud-alert error">
            <?php foreach ($errors as $erro): ?>
                <div><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/dashboard/galeria" enctype="multipart/form-data" class="crud-form" style="margin-bottom: 1.4rem;">
        <?= $csrf ?>
        <div class="crud-form-grid">
            <div class="crud-field">
                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($old['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex.: Batismo nas águas" required>
            </div>
            <div class="crud-field">
                <label for="data_registro">Data (opcional)</label>
                <input type="date" id="data_registro" name="data_registro" value="<?= htmlspecialchars($old['data_registro'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="crud-field">
            <label for="legenda">Legenda (opcional)</label>
            <textarea id="legenda" name="legenda" rows="2" placeholder="Um breve resumo do momento..."><?= htmlspecialchars($old['legenda'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="crud-field">
            <label for="foto">Foto (PNG, JPG, WEBP ou GIF, até 8MB)</label>
            <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp,image/gif" required>
        </div>
        <div class="crud-form-actions" style="justify-content: flex-start;">
            <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-upload"></i> Adicionar à galeria</button>
        </div>
    </form>

    <?php if ($memorias === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-images"></i></div>
            <h2>Nenhuma foto na galeria ainda</h2>
            <p>Adicione a primeira foto de um momento marcante da igreja.</p>
        </div>
    <?php else: ?>
        <div class="projecao-imagens-grid">
            <?php foreach ($memorias as $memoria): ?>
                <div class="projecao-imagem-card galeria-memoria-card">
                    <img src="<?= $basePath ?>/<?= htmlspecialchars($memoria->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($memoria->titulo, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="galeria-memoria-legenda">
                        <strong><?= htmlspecialchars($memoria->titulo, ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if ($memoria->dataRegistro): ?>
                            <span><?= (new DateTimeImmutable($memoria->dataRegistro))->format('d/m/Y') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="projecao-imagem-acoes">
                        <form method="POST" action="<?= $basePath ?>/dashboard/galeria/<?= $memoria->id ?>/excluir" data-confirm="Excluir esta foto?">
                            <?= $csrf ?>
                            <button type="submit" class="btn-k btn-k-ghost" style="color: var(--danger);" title="Excluir">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
