<?php

use Igrejas\Core\View;

/**
 * @var array $config
 * @var \Igrejas\Models\KidsConteudo $conteudo
 * @var \Igrejas\Models\KidsCrianca|null $criancaAtiva
 * @var bool $jaConcluido
 * @var string $csrfToken
 * @var array{xp: int, moedas: int}|null $pontosGanhos
 */
$basePath = $config['base_path'] ?? '';
$tipoInfo = $conteudo->tipoInfo();
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/kids-biblioteca.css?v=<?= View::assetVersion('assets/css/kids-biblioteca.css') ?>">

<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <p style="font-weight:700;"><?= $tipoInfo['emoji'] ?> <?= htmlspecialchars($tipoInfo['label'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a href="<?= $basePath ?>/dashboard/kids/biblioteca/tipo/<?= $conteudo->tipo ?>" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <div class="kids-conteudo-painel">
        <?php if ($pontosGanhos !== null): ?>
            <div class="kids-premio-banner">
                <span class="emoji">🎉</span>
                <span>Você ganhou +<?= $pontosGanhos['xp'] ?> XP e +<?= $pontosGanhos['moedas'] ?> moedas!</span>
            </div>
        <?php endif; ?>

        <div class="capa-grande">
            <?php if ($conteudo->capaPath !== null): ?>
                <img src="<?= $basePath ?>/<?= htmlspecialchars($conteudo->capaPath, ENT_QUOTES, 'UTF-8') ?>" alt="">
            <?php else: ?>
                <?= $tipoInfo['emoji'] ?>
            <?php endif; ?>
        </div>

        <h1><?= htmlspecialchars($conteudo->titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if ($conteudo->descricao): ?>
            <p style="color: var(--kids-texto-suave); font-weight: 600;"><?= htmlspecialchars($conteudo->descricao, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($conteudo->midiaPath !== null): ?>
            <?php if ($conteudo->tipo === 'audio'): ?>
                <audio controls src="<?= $basePath ?>/<?= htmlspecialchars($conteudo->midiaPath, ENT_QUOTES, 'UTF-8') ?>"></audio>
            <?php elseif ($conteudo->tipo === 'video'): ?>
                <video controls src="<?= $basePath ?>/<?= htmlspecialchars($conteudo->midiaPath, ENT_QUOTES, 'UTF-8') ?>"></video>
            <?php else: ?>
                <p><a href="<?= $basePath ?>/<?= htmlspecialchars($conteudo->midiaPath, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="kids-btn-concluir" style="background: linear-gradient(135deg, var(--kids-azul), #2E9FC7); box-shadow: 0 5px 0 #1E7A9C;">
                    <i class="bi bi-download"></i> Abrir arquivo
                </a></p>
            <?php endif; ?>
        <?php elseif ($conteudo->midiaUrl !== null): ?>
            <p><a href="<?= htmlspecialchars($conteudo->midiaUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="kids-btn-concluir" style="background: linear-gradient(135deg, var(--kids-azul), #2E9FC7); box-shadow: 0 5px 0 #1E7A9C;">
                <i class="bi bi-play-circle"></i> Assistir/ouvir
            </a></p>
        <?php endif; ?>

        <?php if ($conteudo->textoConteudo): ?>
            <?php if ($conteudo->origem === 'kadosys' && in_array($conteudo->tipo, ['colorir', 'jogo', 'slide', 'hq', 'atividade'], true)): ?>
                <?= $conteudo->textoConteudo ?>
            <?php else: ?>
                <div class="texto"><?= htmlspecialchars($conteudo->textoConteudo, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($conteudo->tipo === 'quiz' && $conteudo->quizPerguntas !== null): ?>
            <?php foreach ($conteudo->quizPerguntas as $indice => $pergunta): ?>
                <div class="kids-quiz-pergunta">
                    <p><?= ($indice + 1) ?>. <?= htmlspecialchars($pergunta['pergunta'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="kids-quiz-alternativas">
                        <?php foreach ($pergunta['alternativas'] as $altIndice => $alternativa): ?>
                            <div class="kids-quiz-alternativa">
                                <?= $altIndice === (int) $pergunta['correta'] ? '✅' : '⬜' ?>
                                <?= htmlspecialchars($alternativa, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top: 1.6rem;">
            <?php if ($criancaAtiva === null): ?>
                <p class="crud-text-dim">Selecione uma criança na <a href="<?= $basePath ?>/dashboard/kids/biblioteca">Biblioteca</a> para ganhar pontos ao concluir.</p>
            <?php elseif ($jaConcluido): ?>
                <button type="button" class="kids-btn-concluir" disabled><i class="bi bi-check-circle-fill"></i> Já concluído</button>
            <?php else: ?>
                <form method="POST" action="<?= $basePath ?>/dashboard/kids/biblioteca/conteudo/<?= $conteudo->id ?>/concluir">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="kids-btn-concluir">
                        <i class="bi bi-star-fill"></i> Concluir e ganhar +<?= $conteudo->xpRecompensa ?> XP
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= $basePath ?>/assets/js/kids-sons.js?v=<?= View::assetVersion('assets/js/kids-sons.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-interacoes.js?v=<?= View::assetVersion('assets/js/kids-interacoes.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-jogo-memoria.js?v=<?= View::assetVersion('assets/js/kids-jogo-memoria.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-jogo-trivia.js?v=<?= View::assetVersion('assets/js/kids-jogo-trivia.js') ?>"></script>
<script src="<?= $basePath ?>/assets/js/kids-jogo-cacapalavras.js?v=<?= View::assetVersion('assets/js/kids-jogo-cacapalavras.js') ?>"></script>
