<?php

/**
 * @var array $config
 * @var string|null $nomeIgreja
 * @var string|null $logoPath
 * @var array<int, \Igrejas\Models\GaleriaMemoria> $memorias
 */
$basePath = $config['base_path'] ?? '';
$logoUrl = $logoPath ? $basePath . '/' . $logoPath : null;
?>

<div class="avisos-publico-shell galeria-publico-shell">
    <header class="avisos-publico-header">
        <?php if ($logoUrl): ?>
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="avisos-publico-logo">
        <?php endif; ?>
        <h1><?= htmlspecialchars($nomeIgreja ?? 'Nossa igreja', ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Galeria de memórias</p>
    </header>

    <main>
        <?php if ($memorias === []): ?>
            <div class="avisos-publico-vazio">
                <i class="bi bi-images"></i>
                <p>Nenhuma foto na galeria ainda.</p>
            </div>
        <?php else: ?>
            <div class="galeria-publico-grid">
                <?php foreach ($memorias as $memoria): ?>
                    <figure class="galeria-publico-card">
                        <img
                            src="<?= $basePath ?>/<?= htmlspecialchars($memoria->fotoPath, ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($memoria->titulo, ENT_QUOTES, 'UTF-8') ?>"
                            loading="lazy"
                        >
                        <figcaption>
                            <strong><?= htmlspecialchars($memoria->titulo, ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if ($memoria->dataRegistro): ?>
                                <span class="data"><?= (new DateTimeImmutable($memoria->dataRegistro))->format('d/m/Y') ?></span>
                            <?php endif; ?>
                            <?php if ($memoria->legenda): ?>
                                <p><?= nl2br(htmlspecialchars($memoria->legenda, ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php endif; ?>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="avisos-publico-footer">
        <span>KADOSYS Igrejas</span>
    </footer>
</div>
