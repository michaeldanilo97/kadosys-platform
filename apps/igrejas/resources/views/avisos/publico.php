<?php

/**
 * @var array $config
 * @var string|null $nomeIgreja
 * @var string|null $logoPath
 * @var array<int, \Igrejas\Models\ComunicacaoAviso> $avisos
 */
$basePath = $config['base_path'] ?? '';
$logoUrl = $logoPath ? $basePath . '/' . $logoPath : null;
?>

<div class="avisos-publico-shell">
    <header class="avisos-publico-header">
        <?php if ($logoUrl): ?>
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="avisos-publico-logo">
        <?php endif; ?>
        <h1><?= htmlspecialchars($nomeIgreja ?? 'Nossa igreja', ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Quadro de avisos</p>
    </header>

    <main class="avisos-publico-lista">
        <?php if ($avisos === []): ?>
            <div class="avisos-publico-vazio">
                <i class="bi bi-megaphone"></i>
                <p>Nenhum aviso no momento.</p>
            </div>
        <?php else: ?>
            <?php foreach ($avisos as $aviso): ?>
                <article class="avisos-publico-card">
                    <div class="data">
                        <?= $aviso->dataPublicacao ? (new DateTimeImmutable($aviso->dataPublicacao))->format('d/m/Y') : '' ?>
                    </div>
                    <h2><?= htmlspecialchars($aviso->titulo, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p><?= nl2br(htmlspecialchars($aviso->conteudo, ENT_QUOTES, 'UTF-8')) ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <footer class="avisos-publico-footer">
        <span>KADOSYS Igrejas</span>
    </footer>
</div>
