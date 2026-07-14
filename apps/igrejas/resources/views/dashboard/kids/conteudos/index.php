<?php

use Igrejas\Models\KidsConteudo;

/**
 * @var array $config
 * @var array<int, KidsConteudo> $conteudos
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string $tipoFiltro
 * @var string $origemFiltro
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
$qs = static function (array $overrides) use ($search, $tipoFiltro, $origemFiltro): string {
    $params = array_filter([
        'busca' => $search,
        'tipo' => $tipoFiltro,
        'origem' => $origemFiltro,
    ] + $overrides, static fn ($v) => $v !== '');

    return $params === [] ? '' : '?' . http_build_query($params);
};
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Kids &middot; Conteúdos</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> conteúdo<?= $total === 1 ? '' : 's' ?> na biblioteca (oficial KADOSYS + da sua igreja).
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/kids" class="btn-k btn-k-ghost"><i class="bi bi-arrow-left"></i> Kids</a>
        <a href="<?= $basePath ?>/dashboard/kids/biblioteca" class="btn-k btn-k-ghost"><i class="bi bi-stars"></i> Ver biblioteca</a>
        <a href="<?= $basePath ?>/dashboard/kids/conteudos/novo" class="btn-k btn-k-grad">
            <i class="bi bi-plus-lg"></i> Novo conteúdo
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/kids/conteudos" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por título, categoria ou tema..."
        >
        <select name="tipo" onchange="this.form.submit()" style="max-width: 180px;">
            <option value="">Todos os tipos</option>
            <?php foreach (KidsConteudo::TIPOS as $slug => $info): ?>
                <option value="<?= $slug ?>" <?= $tipoFiltro === $slug ? 'selected' : '' ?>>
                    <?= $info['emoji'] ?> <?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="origem" onchange="this.form.submit()" style="max-width: 160px;">
            <option value="">Todas as origens</option>
            <option value="kadosys" <?= $origemFiltro === 'kadosys' ? 'selected' : '' ?>>⭐ KADOSYS</option>
            <option value="igreja" <?= $origemFiltro === 'igreja' ? 'selected' : '' ?>>🏠 Minha igreja</option>
        </select>
        <?php if ($search !== '' || $tipoFiltro !== '' || $origemFiltro !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/kids/conteudos" class="crud-search-clear" aria-label="Limpar filtros">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($conteudos === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-collection-play"></i></div>
            <h2>Nenhum conteúdo encontrado</h2>
            <p>Tente outro filtro ou cadastre o primeiro conteúdo da sua igreja.</p>
        </div>
    <?php else: ?>
        <div class="crud-table-wrapper">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Conteúdo</th>
                        <th>Tipo</th>
                        <th>Origem</th>
                        <th>Idade</th>
                        <th>Status</th>
                        <th class="actions-col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conteudos as $conteudo): ?>
                        <?php $tipoInfo = $conteudo->tipoInfo(); ?>
                        <tr>
                            <td>
                                <div class="crud-person">
                                    <span class="crud-avatar"><?= $tipoInfo['emoji'] ?></span>
                                    <span>
                                        <?= htmlspecialchars($conteudo->titulo, ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($conteudo->categoria): ?>
                                            <div class="crud-text-dim" style="font-size: 0.78rem;"><?= htmlspecialchars($conteudo->categoria, ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($tipoInfo['label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($conteudo->origem === 'kadosys'): ?>
                                    <span class="status-badge is-ativo">⭐ KADOSYS</span>
                                <?php else: ?>
                                    <span class="status-badge is-inativo">🏠 Minha igreja</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $conteudo->idadeMin !== null || $conteudo->idadeMax !== null
                                    ? ($conteudo->idadeMin ?? '0') . '–' . ($conteudo->idadeMax ?? '+')
                                    : '<span class="crud-text-dim">—</span>' ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $conteudo->status === 'publicado' ? 'is-ativo' : 'is-inativo' ?>">
                                    <?= $conteudo->status === 'publicado' ? 'Publicado' : 'Rascunho' ?>
                                </span>
                            </td>
                            <td class="actions-col">
                                <?php if ($conteudo->editavel()): ?>
                                    <a
                                        href="<?= $basePath ?>/dashboard/kids/conteudos/<?= $conteudo->id ?>/editar"
                                        class="crud-icon-btn"
                                        aria-label="Editar <?= htmlspecialchars($conteudo->titulo, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form
                                        method="POST"
                                        action="<?= $basePath ?>/dashboard/kids/conteudos/<?= $conteudo->id ?>/excluir"
                                        data-confirm="Remover &quot;<?= htmlspecialchars($conteudo->titulo, ENT_QUOTES, 'UTF-8') ?>&quot;?"
                                    >
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <button
                                            type="submit"
                                            class="crud-icon-btn danger"
                                            aria-label="Excluir <?= htmlspecialchars($conteudo->titulo, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="crud-text-dim" title="Conteúdo oficial KADOSYS - somente leitura"><i class="bi bi-lock"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($lastPage > 1): ?>
            <div class="crud-pagination">
                <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                    <a href="<?= $basePath ?>/dashboard/kids/conteudos<?= $qs(['pagina' => $i]) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
