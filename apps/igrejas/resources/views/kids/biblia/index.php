<?php

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var bool $textoImportado
 * @var array<int, \Igrejas\Models\BibliaLivro> $livros
 * @var array<int, int> $lidosPorLivro livro_id => quantos capitulos lidos
 */
$basePath = $config['base_path'] ?? '';
$antigo = array_filter($livros, static fn ($l) => $l->testamento === 'antigo');
$novo = array_filter($livros, static fn ($l) => $l->testamento === 'novo');

$renderLivros = static function (array $livros, array $lidosPorLivro, string $basePath): void {
    foreach ($livros as $livro) {
        $lidos = $lidosPorLivro[$livro->id] ?? 0;
        $completo = $lidos >= $livro->totalCapitulos;
        ?>
        <a href="<?= $basePath ?>/kids/biblia/<?= $livro->id ?>" class="kids-livro-card<?= $completo ? ' completo' : '' ?>">
            <span class="kids-livro-abreviacao"><?= htmlspecialchars($livro->abreviacao, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="kids-livro-nome"><?= htmlspecialchars($livro->nome, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="kids-livro-progresso"><?= $lidos ?>/<?= $livro->totalCapitulos ?> <?= $completo ? '🏆' : '' ?></span>
        </a>
        <?php
    }
};
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <h1>📖 Bíblia Interativa</h1>
            <p>Escolha um livro e comece a explorar a Palavra de Deus!</p>
        </div>
        <a href="<?= $basePath ?>/kids" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <?php if (!$textoImportado): ?>
        <div class="kids-vazio">
            📖 O texto da Bíblia ainda não foi importado nesta igreja. Peça
            pra equipe rodar o importador (<code>database/seed_biblia.php</code>)
            pra liberar a leitura aqui!
        </div>
    <?php else: ?>
        <h2 class="kids-secao-titulo">📜 Antigo Testamento</h2>
        <div class="kids-livro-grade">
            <?php $renderLivros($antigo, $lidosPorLivro, $basePath); ?>
        </div>

        <h2 class="kids-secao-titulo">✝️ Novo Testamento</h2>
        <div class="kids-livro-grade">
            <?php $renderLivros($novo, $lidosPorLivro, $basePath); ?>
        </div>
    <?php endif; ?>
</div>
