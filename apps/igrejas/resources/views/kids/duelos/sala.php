<?php

/**
 * @var array $config
 * @var \Igrejas\Models\KidsCrianca $crianca
 * @var array{id: int, conteudoId: int, criadorId: int, convidadoId: int, status: string, criadorProgresso: int, convidadoProgresso: int, criadorTerminou: bool, convidadoTerminou: bool, vencedorId: ?int} $duelo
 * @var \Igrejas\Models\KidsConteudo $conteudo
 * @var array{status: string, meuProgresso: int, oponenteProgresso: int, meuNome: string, oponenteNome: string, meuTerminou: bool, oponenteTerminou: bool, vencedorSouEu: ?bool, reacaoOponente: ?string, reacaoOponenteEm: ?string}|null $estadoInicial
 * @var string $csrfToken
 */
$basePath = $config['base_path'] ?? '';
$totalPerguntas = count($conteudo->quizPerguntas);
$reacoes = ['👍', '😂', '🎉', '😮', '❤️', '💪'];
?>
<div class="kids-mundo">
    <div class="kids-topo">
        <div class="kids-saudacao">
            <p style="font-weight:700;">⚔️ Duelo: <?= htmlspecialchars($conteudo->titulo, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a href="<?= $basePath ?>/kids/duelos" class="kids-voltar"><i class="bi bi-arrow-left"></i> Sair</a>
    </div>

    <div class="kids-duelo-placar">
        <div class="kids-duelo-jogador">
            <span class="nome">Você</span>
            <div class="kids-duelo-barra"><div class="kids-duelo-barra-preenchida" data-minha-barra style="width: 0%;"></div></div>
            <span class="contagem" data-meu-contador>0/<?= $totalPerguntas ?></span>
        </div>
        <span class="kids-duelo-vs">VS</span>
        <div class="kids-duelo-jogador">
            <span class="nome" data-nome-oponente><?= htmlspecialchars($estadoInicial['oponenteNome'] ?? 'Amigo', ENT_QUOTES, 'UTF-8') ?></span>
            <div class="kids-duelo-barra"><div class="kids-duelo-barra-preenchida oponente" data-oponente-barra style="width: 0%;"></div></div>
            <span class="contagem" data-oponente-contador>0/<?= $totalPerguntas ?></span>
        </div>
    </div>

    <div class="kids-conteudo-painel">
        <div data-duelo-perguntas>
            <?php foreach ($conteudo->quizPerguntas as $indice => $pergunta): ?>
                <div class="kids-quiz-pergunta" data-duelo-pergunta data-indice="<?= $indice ?>" <?= $indice === 0 ? '' : 'hidden' ?>>
                    <p><?= ($indice + 1) ?>. <?= htmlspecialchars($pergunta['pergunta'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="kids-quiz-alternativas" data-duelo-alternativas>
                        <?php foreach ($pergunta['alternativas'] as $alternativa): ?>
                            <button type="button" class="kids-quiz-alternativa" data-duelo-alternativa data-correta="<?= $alternativa === $pergunta['alternativas'][(int) $pergunta['correta']] ? '1' : '0' ?>">
                                <?= htmlspecialchars($alternativa, ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div data-duelo-aguardando hidden class="kids-premio-banner">
            <span class="emoji">⏳</span>
            <span>Você terminou! Esperando <span data-nome-oponente-aguardando><?= htmlspecialchars($estadoInicial['oponenteNome'] ?? 'seu amigo', ENT_QUOTES, 'UTF-8') ?></span> terminar...</span>
        </div>

        <div data-duelo-resultado hidden class="kids-premio-banner">
            <span class="emoji" data-resultado-emoji>🏆</span>
            <span data-resultado-texto></span>
        </div>

        <div class="kids-duelo-reacoes">
            <?php foreach ($reacoes as $emoji): ?>
                <button type="button" class="kids-duelo-reacao-btn" data-reagir="<?= $emoji ?>"><?= $emoji ?></button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="kids-duelo-flutuante" data-reacao-flutuante hidden></div>

<script>
    (function () {
        var basePath = <?= json_encode($basePath, JSON_UNESCAPED_SLASHES) ?>;
        var duelId = <?= (int) $duelo['id'] ?>;
        var csrfToken = <?= json_encode($csrfToken) ?>;
        var total = <?= $totalPerguntas ?>;
        var acertei = <?= (int) ($estadoInicial['meuProgresso'] ?? 0) ?>;
        var jaTerminei = <?= !empty($estadoInicial['meuTerminou']) ? 'true' : 'false' ?>;
        var ultimaReacaoVista = null;

        var perguntas = document.querySelectorAll('[data-duelo-pergunta]');
        var perguntasWrapper = document.querySelector('[data-duelo-perguntas]');
        var meuContador = document.querySelector('[data-meu-contador]');
        var minhaBarra = document.querySelector('[data-minha-barra]');
        var oponenteContador = document.querySelector('[data-oponente-contador]');
        var oponenteBarra = document.querySelector('[data-oponente-barra]');
        var aguardando = document.querySelector('[data-duelo-aguardando]');
        var resultado = document.querySelector('[data-duelo-resultado]');
        var resultadoTexto = document.querySelector('[data-resultado-texto]');
        var resultadoEmoji = document.querySelector('[data-resultado-emoji]');
        var flutuante = document.querySelector('[data-reacao-flutuante]');

        function mostrarPergunta(indice) {
            perguntas.forEach(function (bloco) {
                bloco.hidden = parseInt(bloco.dataset.indice, 10) !== indice;
            });
        }

        function atualizarMeuPlacar() {
            meuContador.textContent = acertei + '/' + total;
            minhaBarra.style.width = (acertei / total * 100) + '%';
        }

        if (jaTerminei) {
            perguntasWrapper.hidden = true;
            aguardando.hidden = false;
        } else {
            mostrarPergunta(acertei);
        }
        atualizarMeuPlacar();

        perguntas.forEach(function (bloco) {
            var grupo = bloco.querySelector('[data-duelo-alternativas]');
            var resolvida = false;

            grupo.addEventListener('click', function (evento) {
                var escolhida = evento.target.closest('[data-duelo-alternativa]');

                if (!escolhida || resolvida) {
                    return;
                }

                if (escolhida.dataset.correta === '1') {
                    resolvida = true;
                    escolhida.classList.add('correta');
                    acertei++;
                    atualizarMeuPlacar();
                    enviarProgresso();

                    setTimeout(function () {
                        if (acertei < total) {
                            mostrarPergunta(acertei);
                        } else {
                            perguntasWrapper.hidden = true;
                            aguardando.hidden = false;
                        }
                    }, 500);
                } else {
                    escolhida.classList.add('errada');
                }
            });
        });

        function enviarProgresso() {
            var body = new URLSearchParams();
            body.set('_csrf_token', csrfToken);
            body.set('progresso', String(acertei));
            body.set('terminou', acertei >= total ? '1' : '');

            fetch(basePath + '/kids/duelos/' + duelId + '/progresso', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            })
                .then(function (resposta) { return resposta.json(); })
                .then(processarEstado)
                .catch(function () {});
        }

        function processarEstado(estado) {
            if (!estado || !estado.ok) {
                return;
            }

            oponenteContador.textContent = estado.oponenteProgresso + '/' + total;
            oponenteBarra.style.width = (estado.oponenteProgresso / total * 100) + '%';

            if (estado.reacaoOponente && estado.reacaoOponenteEm && estado.reacaoOponenteEm !== ultimaReacaoVista) {
                ultimaReacaoVista = estado.reacaoOponenteEm;
                mostrarReacaoFlutuante(estado.reacaoOponente);
            }

            if (estado.status === 'finalizado') {
                aguardando.hidden = true;
                resultado.hidden = false;

                if (estado.vencedorSouEu === true) {
                    resultadoEmoji.textContent = '🏆';
                    resultadoTexto.textContent = 'Você venceu o duelo! +20 XP e +12 moedas!';
                } else {
                    resultadoEmoji.textContent = '🎖️';
                    resultadoTexto.textContent = estado.oponenteNome + ' venceu dessa vez - mas você ganhou +5 XP e +3 moedas por participar!';
                }
            }
        }

        function mostrarReacaoFlutuante(emoji) {
            flutuante.textContent = emoji;
            flutuante.hidden = false;
            flutuante.classList.remove('anima');
            void flutuante.offsetWidth;
            flutuante.classList.add('anima');
            setTimeout(function () { flutuante.hidden = true; }, 1400);
        }

        document.querySelectorAll('[data-reagir]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                var body = new URLSearchParams();
                body.set('_csrf_token', csrfToken);
                body.set('emoji', botao.dataset.reagir);

                fetch(basePath + '/kids/duelos/' + duelId + '/reagir', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString(),
                }).catch(function () {});
            });
        });

        function consultarEstado() {
            fetch(basePath + '/kids/duelos/' + duelId + '/estado')
                .then(function (resposta) { return resposta.json(); })
                .then(processarEstado)
                .catch(function () {});
        }

        consultarEstado();
        setInterval(consultarEstado, 1500);
    })();
</script>
