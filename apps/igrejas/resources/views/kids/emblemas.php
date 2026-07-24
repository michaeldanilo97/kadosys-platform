<?php

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var array<int, array{slug: string, nome: string, emoji: string, descricao: string, conquistado: bool, conquistadoEm: ?string}> $emblemas
 */
$basePath = $config['base_path'] ?? '';
$totalConquistados = count(array_filter($emblemas, static fn (array $e): bool => $e['conquistado']));
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <h1>🏅 Meus Emblemas</h1>
            <p>Você já conquistou <?= $totalConquistados ?> de <?= count($emblemas) ?> emblemas!</p>
        </div>
        <a href="<?= $basePath ?>/kids" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <div class="kids-emblema-grade">
        <?php foreach ($emblemas as $emblema): ?>
            <div class="kids-emblema-card<?= $emblema['conquistado'] ? ' conquistado' : '' ?>">
                <span class="kids-emblema-emoji"><?= $emblema['emoji'] ?></span>
                <span class="kids-emblema-nome"><?= htmlspecialchars($emblema['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="kids-emblema-descricao"><?= htmlspecialchars($emblema['descricao'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($emblema['conquistado']): ?>
                    <span class="kids-emblema-selo"><i class="bi bi-check-circle-fill"></i> Conquistado</span>
                <?php else: ?>
                    <span class="kids-emblema-cadeado"><i class="bi bi-lock-fill"></i> Ainda não</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
