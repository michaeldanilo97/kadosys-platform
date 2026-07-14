<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var array<int, \Igrejas\Models\KidsCrianca> $criancas
 * @var int $total
 * @var int $page
 * @var int $lastPage
 * @var string $search
 * @var string|null $success
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/kids.css?v=<?= View::assetVersion('assets/css/kids.css') ?>">

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Kids &middot; Crianças</h1>
        <p class="dash-page-subtitle">
            <?= $total ?> criança<?= $total === 1 ? '' : 's' ?> cadastrada<?= $total === 1 ? '' : 's' ?>.
        </p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/kids" class="btn-k btn-k-ghost"><i class="bi bi-arrow-left"></i> Kids</a>
        <a href="<?= $basePath ?>/dashboard/kids/criancas/novo" class="btn-k btn-k-grad">
            <i class="bi bi-person-plus"></i> Nova criança
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="dash-panel">
    <form method="GET" action="<?= $basePath ?>/dashboard/kids/criancas" class="crud-search">
        <i class="bi bi-search"></i>
        <input
            type="search"
            name="busca"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por nome, turma ou responsável..."
        >
        <?php if ($search !== ''): ?>
            <a href="<?= $basePath ?>/dashboard/kids/criancas" class="crud-search-clear" aria-label="Limpar busca">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <?php if ($criancas === []): ?>
        <div class="crud-empty">
            <div class="icon"><i class="bi bi-emoji-laughing"></i></div>
            <h2><?= $search !== '' ? 'Nenhuma criança encontrada' : 'Nenhuma criança cadastrada ainda' ?></h2>
            <p>
                <?= $search !== ''
                    ? 'Tente buscar por outro nome, turma ou responsável.'
                    : 'Comece cadastrando a primeira criança do ministério infantil.' ?>
            </p>
            <?php if ($search === ''): ?>
                <a href="<?= $basePath ?>/dashboard/kids/criancas/novo" class="btn-k btn-k-grad">
                    <i class="bi bi-person-plus"></i> Cadastrar criança
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="kids-crianca-grade">
            <?php foreach ($criancas as $crianca): ?>
                <a href="<?= $basePath ?>/dashboard/kids/criancas/<?= $crianca->id ?>" class="kids-crianca-card">
                    <div class="kids-crianca-card-foto">
                        <?php if ($crianca->fotoPath !== null): ?>
                            <img src="<?= $basePath ?>/<?= htmlspecialchars($crianca->fotoPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <span class="kids-crianca-card-inicial"><?= htmlspecialchars(mb_strtoupper(mb_substr($crianca->nome, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="kids-crianca-card-nome"><?= htmlspecialchars($crianca->nome, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="kids-crianca-card-turma">
                        <?= $crianca->turmaNome !== null ? htmlspecialchars($crianca->turmaNome, ENT_QUOTES, 'UTF-8') : 'Sem turma' ?>
                        <?= $crianca->idade() !== null ? ' &middot; ' . $crianca->idade() . ' anos' : '' ?>
                    </div>

                    <span class="status-badge kids-crianca-card-status <?= $crianca->status === 'ativo' ? 'is-ativo' : 'is-inativo' ?>">
                        <?= $crianca->status === 'ativo' ? 'Ativo' : 'Inativo' ?>
                    </span>

                    <div class="kids-crianca-card-stats">
                        <span title="Experiência"><i class="bi bi-star-fill"></i> <?= $crianca->xp ?> XP</span>
                        <span title="Moedas"><i class="bi bi-coin"></i> <?= $crianca->moedas ?></span>
                        <span title="Sequência de presença"><i class="bi bi-fire"></i> <?= $crianca->sequenciaDias ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($lastPage > 1): ?>
            <div class="crud-pagination">
                <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                    <a
                        href="<?= $basePath ?>/dashboard/kids/criancas?pagina=<?= $i ?><?= $search !== '' ? '&busca=' . urlencode($search) : '' ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
