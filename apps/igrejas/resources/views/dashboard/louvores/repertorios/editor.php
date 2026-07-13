<?php

/**
 * @var array $config
 * @var \Igrejas\Models\Repertorio $repertorio
 * @var array<int, array{id: int, titulo: string}> $louvoresDisponiveis
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= htmlspecialchars($repertorio->titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="dash-page-subtitle">Arraste os louvores pra reordenar - a mudança é vista ao vivo por todo o time no Modo Culto.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/louvores/repertorios" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <a href="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/culto" target="_blank" rel="noopener" class="btn-k btn-k-grad">
            <i class="bi bi-broadcast"></i> Abrir Modo Culto
        </a>
    </div>
</div>

<div
    class="dash-panels-row"
    data-repertorio-editor
    data-repertorio-id="<?= $repertorio->id ?>"
    data-estado-url="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/estado"
    data-adicionar-url="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/itens"
    data-reordenar-url="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/reordenar"
    data-remover-url-base="<?= $basePath ?>/dashboard/louvores/repertorios/<?= $repertorio->id ?>/itens"
    data-csrf="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
>
    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-list-ol"></i> Ordem do culto</h2>
        </div>
        <ul class="repertorio-lista" data-repertorio-lista>
            <?php foreach ($repertorio->itens as $item): ?>
                <li class="repertorio-item" draggable="true" data-item-id="<?= $item->id ?>">
                    <span class="repertorio-item-arrasta"><i class="bi bi-grip-vertical"></i></span>
                    <span class="repertorio-item-titulo"><?= htmlspecialchars($item->tituloLouvor, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="repertorio-item-meta">
                        <?= $item->tomAtual !== null ? htmlspecialchars($item->tomAtual, ENT_QUOTES, 'UTF-8') : '' ?>
                        <?= $item->andamentoBpm !== null ? ' · ' . $item->andamentoBpm . ' BPM' : '' ?>
                    </span>
                    <button type="button" class="crud-icon-btn danger" data-repertorio-remover aria-label="Remover">
                        <i class="bi bi-trash"></i>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="crud-text-dim" data-repertorio-vazio <?= $repertorio->itens !== [] ? 'hidden' : '' ?>>
            Nenhum louvor adicionado ainda - escolha ao lado.
        </p>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-plus-circle"></i> Adicionar louvor</h2>
        </div>
        <div class="crud-search" style="margin-bottom: 1rem;">
            <i class="bi bi-search"></i>
            <input type="search" placeholder="Buscar louvor..." data-repertorio-busca>
        </div>
        <ul class="repertorio-disponiveis" data-repertorio-disponiveis>
            <?php foreach ($louvoresDisponiveis as $louvor): ?>
                <li class="repertorio-disponivel-item" data-louvor-id="<?= $louvor['id'] ?>" data-louvor-titulo="<?= htmlspecialchars(mb_strtolower($louvor['titulo']), ENT_QUOTES, 'UTF-8') ?>">
                    <span><?= htmlspecialchars($louvor['titulo'], ENT_QUOTES, 'UTF-8') ?></span>
                    <button type="button" class="btn-k btn-k-ghost" data-repertorio-adicionar>
                        <i class="bi bi-plus-lg"></i> Adicionar
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/repertorio-editor.js?v=<?= \Igrejas\Core\View::assetVersion('assets/js/repertorio-editor.js') ?>"></script>
