<?php
/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var \Igrejas\Models\KidsConteudo $conteudo
 * @var bool $jaConcluido
 * @var string $csrfToken
 * @var int $custoAjudaQuiz
 * @var array{xp: int, moedas: int}|null $pontosGanhos
 */
$basePath = $config['base_path'] ?? '';
$tipoInfo = $conteudo->tipoInfo();
$isQuiz = $conteudo->tipo === 'quiz' && $conteudo->quizPerguntas !== null;
// "Complete o Versiculo", "Ligue o Personagem" (tipo atividade) e os
// jogos com niveis/gate proprio (memoria, trivia, caca-palavras - ver
// kids-jogo-*.js) tem o mesmo mecanismo de progresso do quiz, embutido no
// proprio HTML confiavel - reconhecido pelos data-attributes do widget,
// sem precisar de uma coluna nova so pra isso.
$temGateProgresso = $isQuiz || ($conteudo->textoConteudo !== null
    && (str_contains($conteudo->textoConteudo, 'data-completar')
        || str_contains($conteudo->textoConteudo, 'data-ligar')
        || str_contains($conteudo->textoConteudo, 'data-jogo-memoria')
        || str_contains($conteudo->textoConteudo, 'data-jogo-trivia')
        || str_contains($conteudo->textoConteudo, 'data-cacapalavras')));
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <p style="font-weight:700;"><?= $tipoInfo['emoji'] ?> <?= htmlspecialchars($tipoInfo['label'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php if ($isQuiz): ?>
            <span class="kids-stats-mini"><span data-kids-moedas>🪙 <?= $crianca->moedas ?></span></span>
        <?php endif; ?>
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
            <?php if ($conteudo->origem === 'kadosys' && in_array($conteudo->tipo, ['colorir', 'jogo', 'slide', 'hq', 'atividade'], true)): ?>
                <?= $conteudo->textoConteudo ?>
            <?php else: ?>
                <div class="texto"><?= htmlspecialchars($conteudo->textoConteudo, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($isQuiz): ?>
            <p class="kids-quiz-progresso" data-quiz-progresso-texto>0 de <?= count($conteudo->quizPerguntas) ?> respondidas certas</p>

            <?php foreach ($conteudo->quizPerguntas as $indice => $pergunta): ?>
                <div class="kids-quiz-pergunta" data-quiz-pergunta data-indice="<?= $indice ?>">
                    <p><?= ($indice + 1) ?>. <?= htmlspecialchars($pergunta['pergunta'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="kids-quiz-alternativas" data-quiz-alternativas>
                        <?php foreach ($pergunta['alternativas'] as $altIndice => $alternativa): ?>
                            <button
                                type="button"
                                class="kids-quiz-alternativa"
                                data-quiz-alternativa
                                data-indice="<?= $altIndice ?>"
                                data-correta="<?= $altIndice === (int) $pergunta['correta'] ? '1' : '0' ?>"
                            >
                                <?= htmlspecialchars($alternativa, ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($pergunta['explicacao'])): ?>
                        <p class="kids-quiz-explicacao" data-quiz-explicacao hidden><i class="bi bi-book-fill"></i> <?= htmlspecialchars($pergunta['explicacao'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <button type="button" class="kids-quiz-ajuda-btn" data-quiz-ajuda>🪙 Pedir ajuda (-<?= $custoAjudaQuiz ?> moedas)</button>
                </div>
            <?php endforeach; ?>
            <script>
                (function () {
                    // O formulario de "Concluir" fica depois deste bloco no HTML
                    // (fora do if de quiz), entao ainda nao existe no DOM quando
                    // este <script> roda - so pode ser lido apos o documento
                    // terminar de parsear.
                    document.addEventListener('DOMContentLoaded', iniciar);

                    function iniciar() {
                    var basePath = <?= json_encode($basePath, JSON_UNESCAPED_SLASHES) ?>;
                    var conteudoId = <?= (int) $conteudo->id ?>;
                    var csrfToken = <?= json_encode($csrfToken) ?>;
                    var moedasEl = document.querySelector('[data-kids-moedas]');
                    var progressoEl = document.querySelector('[data-quiz-progresso-texto]');
                    var formConcluir = document.querySelector('[data-quiz-form-concluir]');
                    var avisoPendente = document.querySelector('[data-quiz-aviso-pendente]');
                    var blocos = document.querySelectorAll('[data-quiz-pergunta]');
                    var total = blocos.length;
                    var respondidasCerto = 0;

                    function atualizarProgresso() {
                        if (progressoEl) {
                            progressoEl.textContent = respondidasCerto + ' de ' + total + ' respondidas certas';
                        }

                        if (respondidasCerto >= total) {
                            if (formConcluir) {
                                formConcluir.hidden = false;
                            }

                            if (avisoPendente) {
                                avisoPendente.hidden = true;
                            }
                        }
                    }

                    blocos.forEach(function (bloco) {
                        var grupo = bloco.querySelector('[data-quiz-alternativas]');
                        var explicacaoEl = bloco.querySelector('[data-quiz-explicacao]');
                        var ajudaBtn = bloco.querySelector('[data-quiz-ajuda]');
                        var resolvida = false;

                        grupo.addEventListener('click', function (event) {
                            var escolhida = event.target.closest('[data-quiz-alternativa]');

                            if (!escolhida || resolvida || escolhida.disabled) {
                                return;
                            }

                            grupo.querySelectorAll('[data-quiz-alternativa]').forEach(function (botao) {
                                botao.classList.remove('correta', 'errada');
                            });

                            if (explicacaoEl) {
                                explicacaoEl.hidden = false;
                                explicacaoEl.classList.remove('correta', 'errada');
                            }

                            if (escolhida.dataset.correta === '1') {
                                escolhida.classList.add('correta');
                                grupo.querySelectorAll('[data-quiz-alternativa]').forEach(function (botao) {
                                    botao.disabled = true;
                                });

                                if (explicacaoEl) {
                                    explicacaoEl.classList.add('correta');
                                }

                                if (ajudaBtn) {
                                    ajudaBtn.hidden = true;
                                }

                                resolvida = true;
                                respondidasCerto++;
                                atualizarProgresso();
                            } else {
                                escolhida.classList.add('errada');

                                if (explicacaoEl) {
                                    explicacaoEl.classList.add('errada');
                                }
                                // Nao desabilita nada aqui: a crianca pode tentar
                                // de novo ate acertar (ver Ajuste 155).
                            }
                        });

                        if (ajudaBtn) {
                            ajudaBtn.addEventListener('click', function () {
                                if (resolvida) {
                                    return;
                                }

                                ajudaBtn.disabled = true;

                                var corpo = new URLSearchParams();
                                corpo.set('_csrf_token', csrfToken);
                                corpo.set('pergunta_indice', bloco.dataset.indice);

                                fetch(basePath + '/kids/conteudo/' + conteudoId + '/quiz-ajuda', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: corpo.toString(),
                                })
                                    .then(function (resposta) { return resposta.json(); })
                                    .then(function (dados) {
                                        if (moedasEl && typeof dados.moedas === 'number') {
                                            moedasEl.textContent = '🪙 ' + dados.moedas;
                                        }

                                        if (!dados.ok) {
                                            ajudaBtn.disabled = false;
                                            ajudaBtn.textContent = '🪙 Moedas insuficientes';

                                            return;
                                        }

                                        (dados.remover || []).forEach(function (indiceAlt) {
                                            var botao = grupo.querySelector('[data-quiz-alternativa][data-indice="' + indiceAlt + '"]');

                                            if (botao) {
                                                botao.classList.add('kids-quiz-alternativa-escondida');
                                                botao.disabled = true;
                                            }
                                        });

                                        ajudaBtn.hidden = true;
                                    })
                                    .catch(function () {
                                        ajudaBtn.disabled = false;
                                    });
                            });
                        }
                    });

                    atualizarProgresso();
                    }
                })();
            </script>
        <?php endif; ?>

        <div style="margin-top: 1.6rem;">
            <?php if ($jaConcluido): ?>
                <button type="button" class="kids-btn-concluir" disabled><i class="bi bi-check-circle-fill"></i> Já concluído</button>
            <?php else: ?>
                <form method="POST" action="<?= $basePath ?>/kids/conteudo/<?= $conteudo->id ?>/concluir" data-quiz-form-concluir <?= $temGateProgresso ? 'hidden' : '' ?>>
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="kids-btn-concluir">
                        <i class="bi bi-star-fill"></i> Concluir e ganhar +<?= $conteudo->xpRecompensa ?> XP
                    </button>
                </form>
                <?php if ($temGateProgresso): ?>
                    <p class="form-field-hint" data-quiz-aviso-pendente>Responda/ligue tudo certinho pra liberar o botão de concluir. Errou? Sem problema, é só tentar de novo! 💪</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
