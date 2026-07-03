<?php

/**
 * @var array $config
 * @var \Igrejas\Models\ComunicacaoAviso $aviso
 */
$basePath = $config['base_path'] ?? '';

$statusLabels = ['rascunho' => 'Rascunho', 'publicado' => 'Publicado', 'arquivado' => 'Arquivado'];
$publicoLabels = ['todos' => 'Todos os membros', 'lideranca' => 'So lideranca'];
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title"><?= htmlspecialchars($aviso->titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="dash-page-subtitle">
            <?= $aviso->dataPublicacao ? (new DateTimeImmutable($aviso->dataPublicacao))->format('d/m/Y') : 'Sem data de publicacao' ?>
            &middot; <?= $publicoLabels[$aviso->publicoAlvo] ?? $aviso->publicoAlvo ?>
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/comunicacao" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <a href="<?= $basePath ?>/dashboard/comunicacao/<?= $aviso->id ?>/editar" class="btn-k btn-k-grad">
            <i class="bi bi-pencil"></i> Editar
        </a>
    </div>
</div>

<div class="dash-panel">
    <div class="aviso-show-meta">
        <span class="status-badge <?= $aviso->status === 'publicado' ? 'is-ativo' : 'is-inativo' ?>">
            <?= $statusLabels[$aviso->status] ?? $aviso->status ?>
        </span>
    </div>

    <div class="aviso-show-conteudo">
        <?= nl2br(htmlspecialchars($aviso->conteudo, ENT_QUOTES, 'UTF-8')) ?>
    </div>
</div>
