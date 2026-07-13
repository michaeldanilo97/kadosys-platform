<?php
/**
 * @var array $config
 * @var array<int, \Igrejas\Models\FinanceiroCategoria> $categorias
 * @var string|null $success
 * @var array $errors
 * @var string $csrfToken
 * @var string $csrf
 */
$basePath = $config['base_path'] ?? '';
$entradas = array_filter($categorias, static fn ($categoria) => $categoria->tipo === 'entrada');
$saidas = array_filter($categorias, static fn ($categoria) => $categoria->tipo === 'saida');
?>

<div class="dash-page-head">
    <div>
        <h1 class="dash-page-title">Categorias financeiras</h1>
        <p class="dash-page-subtitle">Organize os lançamentos de entrada e saída por categoria.</p>
    </div>
    <div class="dash-page-actions">
        <a href="<?= $basePath ?>/dashboard/financeiro" class="btn-k btn-k-ghost">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="crud-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

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
    <div class="dash-panel-head">
        <h2><i class="bi bi-plus-circle"></i> Nova categoria</h2>
    </div>
    <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/categorias" class="crud-inline-form">
        <?= $csrf ?>
        <input type="text" name="nome" placeholder="Nome da categoria" required>
        <select name="tipo" required>
            <option value="entrada">Entrada</option>
            <option value="saida">Saída</option>
        </select>
        <button type="submit" class="btn-k btn-k-grad"><i class="bi bi-plus-lg"></i> Adicionar</button>
    </form>
</div>

<div class="dash-panels-row">
    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-arrow-down-circle"></i> Categorias de entrada</h2>
            <span class="panel-badge"><?= count($entradas) ?></span>
        </div>
        <?php if ($entradas === []): ?>
            <p class="crud-text-dim">Nenhuma categoria de entrada cadastrada.</p>
        <?php else: ?>
            <ul class="crud-people-list">
                <?php foreach ($entradas as $categoria): ?>
                    <li>
                        <div class="crud-person">
                            <span><?= htmlspecialchars($categoria->nome, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="status-badge <?= $categoria->status === 'ativa' ? 'is-ativo' : 'is-inativo' ?>">
                                <?= $categoria->status === 'ativa' ? 'Ativa' : 'Inativa' ?>
                            </span>
                        </div>
        <div style="display: flex; gap: 0.4rem;">
                            <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/categorias/<?= $categoria->id ?>/status">
                                <?= $csrf ?>
                                <button type="submit" class="crud-icon-btn" aria-label="Alternar status">
                                    <i class="bi <?= $categoria->status === 'ativa' ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/categorias/<?= $categoria->id ?>/excluir" data-confirm="Remover a categoria &quot;<?= htmlspecialchars($categoria->nome, ENT_QUOTES, 'UTF-8') ?>&quot;? Os lançamentos já cadastrados ficam sem categoria.">
                                <?= $csrf ?>
                                <button type="submit" class="crud-icon-btn danger" aria-label="Excluir categoria">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <h2><i class="bi bi-arrow-up-circle"></i> Categorias de saída</h2>
            <span class="panel-badge"><?= count($saidas) ?></span>
        </div>
        <?php if ($saidas === []): ?>
            <p class="crud-text-dim">Nenhuma categoria de saída cadastrada.</p>
        <?php else: ?>
            <ul class="crud-people-list">
                <?php foreach ($saidas as $categoria): ?>
                    <li>
                        <div class="crud-person">
                            <span><?= htmlspecialchars($categoria->nome, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="status-badge <?= $categoria->status === 'ativa' ? 'is-ativo' : 'is-inativo' ?>">
                                <?= $categoria->status === 'ativa' ? 'Ativa' : 'Inativa' ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 0.4rem;">
                            <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/categorias/<?= $categoria->id ?>/status">
                                <?= $csrf ?>
                                <button type="submit" class="crud-icon-btn" aria-label="Alternar status">
                                    <i class="bi <?= $categoria->status === 'ativa' ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= $basePath ?>/dashboard/financeiro/categorias/<?= $categoria->id ?>/excluir" data-confirm="Remover a categoria &quot;<?= htmlspecialchars($categoria->nome, ENT_QUOTES, 'UTF-8') ?>&quot;? Os lançamentos já cadastrados ficam sem categoria.">
                                <?= $csrf ?>
                                <button type="submit" class="crud-icon-btn danger" aria-label="Excluir categoria">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
