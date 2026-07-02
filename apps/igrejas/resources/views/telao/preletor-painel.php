<?php
/**
 * @var array $config
 * @var string $token
 * @var array<int, \Igrejas\Models\BibliaLivro> $livros
 * @var array<string, string> $versoes
 */
$basePath = $config['base_path'] ?? '';
?>

<div class="preletor-shell" data-preletor data-poll-url="<?= $basePath ?>/projecao/<?= urlencode($token) ?>/estado" data-biblia-url="<?= $basePath ?>/projecao/<?= urlencode($token) ?>/biblia">
    <header class="preletor-topbar">
        <span class="brand"><span class="dot"></span> Preletor &middot; ao vivo</span>
        <form method="POST" action="<?= $basePath ?>/preletor/sair">
            <button type="submit" class="preletor-sair"><i class="bi bi-box-arrow-right"></i> Sair</button>
        </form>
    </header>

    <div class="preletor-body">
        <form class="preletor-picker" data-preletor-form onsubmit="return false;">
            <select name="biblia_versao" data-campo="biblia_versao" required>
                <?php foreach ($versoes as $codigo => $nome): ?>
                    <option value="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="livro_id" data-campo="livro_id" required>
                <option value="" selected disabled>Livro...</option>
                <?php foreach ($livros as $livro): ?>
                    <option value="<?= $livro->id ?>" data-total-capitulos="<?= $livro->totalCapitulos ?>">
                        <?= htmlspecialchars($livro->nome, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="capitulo" data-campo="capitulo" placeholder="Cap." min="1" required>
            <input type="number" name="versiculo_inicio" data-campo="versiculo_inicio" placeholder="Vers." min="1" required>
            <input type="number" name="versiculo_fim" data-campo="versiculo_fim" placeholder="Ate (opcional)" min="1">
            <button type="submit" data-preletor-projetar><i class="bi bi-broadcast"></i> Projetar</button>
        </form>

        <div class="preletor-canvas-wrap">
            <div class="preletor-texto" data-preletor-texto>
                <p class="preletor-empty">Escolha a versao, livro, capitulo e versiculo acima para projetar.</p>
            </div>
            <canvas class="preletor-canvas" data-preletor-canvas></canvas>
            <div class="preletor-tools">
                <button type="button" class="preletor-tool-btn" data-tool-pen aria-label="Caneta">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button type="button" class="preletor-tool-btn preletor-tool-btn-wide" data-tool-clear aria-label="Apagar marcacoes">
                    <i class="bi bi-eraser-fill"></i> Apagar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/preletor.js"></script>
