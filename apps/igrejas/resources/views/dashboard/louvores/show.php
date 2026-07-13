<?php

/**
 * @var array $config
 * @var \Igrejas\Models\Louvor $louvor
 * @var array<int, \Igrejas\Models\LouvorTomHistorico> $historico
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= htmlspecialchars($louvor->titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="dash-page-subtitle">
            <?= $louvor->tomAtual !== null
                ? 'Tom atual: ' . htmlspecialchars($louvor->tomAtual, ENT_QUOTES, 'UTF-8')
                : 'Sem tom definido' ?>
            <?php if ($louvor->playbackTitulo !== null): ?>
                &middot; Áudio: <?= htmlspecialchars($louvor->playbackTitulo, ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/louvores" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <a href="<?= $basePath ?>/dashboard/louvores/<?= $louvor->id ?>/editar" class="btn-k btn-k-grad">
            <i class="bi bi-pencil"></i> Editar
        </a>
    </div>
</div>

<div class="dash-panels-row">
    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-file-text"></i> Letra</h2>
        </div>
        <?php if ($louvor->letra !== null && trim($louvor->letra) !== ''): ?>
            <div class="aviso-show-conteudo"><?= nl2br(htmlspecialchars($louvor->letra, ENT_QUOTES, 'UTF-8')) ?></div>
        <?php else: ?>
            <p class="crud-text-dim">Letra ainda não cadastrada.</p>
        <?php endif; ?>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-music-note-beamed"></i> Cifra</h2>
        </div>
        <?php if ($louvor->cifra !== null && trim($louvor->cifra) !== ''): ?>
            <pre class="crud-field-mono" style="white-space: pre-wrap; margin: 0;"><?= htmlspecialchars($louvor->cifra, ENT_QUOTES, 'UTF-8') ?></pre>
        <?php else: ?>
            <p class="crud-text-dim">Cifra ainda não cadastrada.</p>
        <?php endif; ?>
        <?php if ($louvor->anexoPath !== null): ?>
            <p style="margin-top: 1rem;">
                <a href="<?= $basePath ?>/<?= htmlspecialchars($louvor->anexoPath, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn-k btn-k-ghost">
                    <i class="bi bi-paperclip"></i> <?= htmlspecialchars($louvor->anexoNomeOriginal ?? 'Ver anexo', ENT_QUOTES, 'UTF-8') ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>

<div class="dash-panel" style="margin-top: 1.1rem;">
    <div class="dash-panel-head">
        <h2><i class="bi bi-clock-history"></i> Histórico de mudanças de tom</h2>
    </div>
    <?php if ($historico === []): ?>
        <p class="crud-text-dim">Nenhuma mudança de tom registrada ainda.</p>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Quando</th>
                        <th>Tom</th>
                        <th>Quem mudou</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $entrada): ?>
                        <tr>
                            <td><?= (new DateTimeImmutable($entrada->createdAt))->format('d/m/Y H:i') ?></td>
                            <td>
                                <?php if ($entrada->tomAnterior !== null): ?>
                                    <span class="crud-text-dim"><?= htmlspecialchars($entrada->tomAnterior, ENT_QUOTES, 'UTF-8') ?></span>
                                    <i class="bi bi-arrow-right"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($entrada->tomNovo, ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><?= $entrada->alteradoPorNome !== null ? htmlspecialchars($entrada->alteradoPorNome, ENT_QUOTES, 'UTF-8') : '<span class="crud-text-dim">&mdash;</span>' ?></td>
                            <td><?= $entrada->observacao !== null ? htmlspecialchars($entrada->observacao, ENT_QUOTES, 'UTF-8') : '<span class="crud-text-dim">&mdash;</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
