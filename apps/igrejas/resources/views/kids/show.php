<?php
/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var \Igrejas\Models\KidsConteudo $conteudo
 * @var bool $jaConcluido
 * @var string $csrfToken
 * @var array{xp: int, moedas: int}|null $pontosGanhos
 */
$basePath = $config['base_path'] ?? '';
$tipoInfo = $conteudo->tipoInfo();
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <p style="font-weight:700;"><?= $tipoInfo['emoji'] ?> <?= htmlspecialchars($tipoInfo['label'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a href="<?= $basePath ?>/kids/tipo/<?= $conteudo->tipo ?>" class="kids-voltar"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <div class="kids-conteudo-painel">
        <?php if ($pontosGanhos !== null): ?>
            <div class="kids-premio-banner">
                <?php $confetes = ['🎊', '⭐', '✨', '🎉', '💛']; ?>
                <?php foreach ($confetes as $i => $confete): ?>
                    <span class="kids-premio-confete" style="left: <?= 8 + $i * 20 ?>%; animation-delay: <?= $i * 0.08 ?>s;"><?= $confete ?></span>
                <?php endforeach; ?>
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
            <?php if ($conteudo->origem === 'kadosys' && in_array($conteudo->tipo, ['colorir', 'jogo', 'slide', 'hq'], true)): ?>
                <?= $conteudo->textoConteudo ?>
            <?php else: ?>
                <div class="texto"><?= htmlspecialchars($conteudo->textoConteudo, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($conteudo->tipo === 'quiz' && $conteudo->quizPerguntas !== null): ?>
            <?php foreach ($conteudo->quizPerguntas as $indice => $pergunta): ?>
                <div class="kids-quiz-pergunta">
                    <p><?= ($indice + 1) ?>. <?= htmlspecialchars($pergunta['pergunta'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="kids-quiz-alternativas" data-quiz-alternativas>
                        <?php foreach ($pergunta['alternativas'] as $altIndice => $alternativa): ?>
                            <button
                                type="button"
                                class="kids-quiz-alternativa"
                                data-correta="<?= $altIndice === (int) $pergunta['correta'] ? '1' : '0' ?>"
                            >
                                <?= htmlspecialchars($alternativa, ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <script>
                document.querySelectorAll('[data-quiz-alternativas]').forEach(function (grupo) {
                    grupo.addEventListener('click', function (event) {
                        var escolhida = event.target.closest('.kids-quiz-alternativa');
                        if (!escolhida || grupo.classList.contains('respondida')) {
                            return;
                        }
                        grupo.classList.add('respondida');
                        grupo.querySelectorAll('.kids-quiz-alternativa').forEach(function (botao) {
                            botao.disabled = true;
                            if (botao.dataset.correta === '1') {
                                botao.classList.add('correta');
                            } else if (botao === escolhida) {
                                botao.classList.add('errada');
                            }
                        });
                    });
                });
            </script>
        <?php endif; ?>

        <div style="margin-top: 1.6rem;">
            <?php if ($jaConcluido): ?>
                <button type="button" class="kids-btn-concluir" disabled><i class="bi bi-check-circle-fill"></i> Já concluído</button>
            <?php else: ?>
                <form method="POST" action="<?= $basePath ?>/kids/conteudo/<?= $conteudo->id ?>/concluir">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="kids-btn-concluir">
                        <i class="bi bi-star-fill"></i> Concluir e ganhar +<?= $conteudo->xpRecompensa ?> XP
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
