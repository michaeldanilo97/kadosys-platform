/**
 * Motor generico de trivia em rodadas (evolucao do "Corrida da Fe" da
 * migracao 058). Cada conteudo declara `data-rodadas` (lista de rodadas,
 * cada uma com titulo + perguntas de multipla escolha); o motor so libera
 * a proxima rodada quando todas as perguntas da rodada atual estiverem
 * certas - erro nao trava, a crianca pode tentar de novo. Reusa as
 * classes .kids-quiz-pergunta/.kids-quiz-alternativas/.kids-quiz-alternativa
 * ja usadas pelo quiz, herdando o mesmo visual/som/animacao.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-jogo-trivia]').forEach(iniciar);
    });

    function iniciar(container) {
        var rodadas;

        try {
            rodadas = JSON.parse(container.getAttribute('data-rodadas'));
        } catch (erro) {
            rodadas = [];
        }

        if (!rodadas.length) {
            return;
        }

        var areaPerguntas = container.querySelector('[data-trivia-perguntas]');
        var tituloEl = container.querySelector('[data-trivia-titulo]');
        var estrelasEl = container.querySelector('[data-trivia-estrelas]');

        if (!areaPerguntas) {
            return;
        }

        var totalPerguntas = 0;
        rodadas.forEach(function (rodada) {
            totalPerguntas += rodada.perguntas.length;
        });

        var acertosTotais = 0;
        var rodadaIndice = 0;

        function atualizarEstrelas() {
            if (estrelasEl) {
                estrelasEl.textContent = '⭐ ' + acertosTotais + '/' + totalPerguntas;
            }
        }

        function renderizarRodada() {
            var rodada = rodadas[rodadaIndice];

            if (tituloEl) {
                tituloEl.textContent = (rodada.titulo || ('Rodada ' + (rodadaIndice + 1))) + ' — ' + (rodadaIndice + 1) + '/' + rodadas.length;
            }

            areaPerguntas.innerHTML = '';

            var certasNaRodada = 0;
            var totalNaRodada = rodada.perguntas.length;

            rodada.perguntas.forEach(function (pergunta) {
                var bloco = document.createElement('div');
                bloco.className = 'kids-quiz-pergunta';

                var textoP = document.createElement('p');
                textoP.textContent = pergunta.pergunta;
                bloco.appendChild(textoP);

                var grupo = document.createElement('div');
                grupo.className = 'kids-quiz-alternativas';

                var resolvida = false;

                pergunta.alternativas.forEach(function (alternativa, indiceAlt) {
                    var botao = document.createElement('button');
                    botao.type = 'button';
                    botao.className = 'kids-quiz-alternativa';
                    botao.textContent = alternativa;
                    botao.setAttribute('data-correta', indiceAlt === pergunta.correta ? '1' : '0');
                    grupo.appendChild(botao);
                });

                grupo.addEventListener('click', function (event) {
                    var escolhida = event.target.closest('.kids-quiz-alternativa');

                    if (!escolhida || resolvida || escolhida.disabled) {
                        return;
                    }

                    grupo.querySelectorAll('.kids-quiz-alternativa').forEach(function (botao) {
                        botao.classList.remove('correta', 'errada');
                    });

                    if (escolhida.getAttribute('data-correta') === '1') {
                        escolhida.classList.add('correta');

                        grupo.querySelectorAll('.kids-quiz-alternativa').forEach(function (botao) {
                            botao.disabled = true;
                        });

                        resolvida = true;
                        certasNaRodada++;
                        acertosTotais++;
                        atualizarEstrelas();

                        if (certasNaRodada === totalNaRodada) {
                            avancarRodada();
                        }
                    } else {
                        escolhida.classList.add('errada');
                    }
                });

                bloco.appendChild(grupo);
                areaPerguntas.appendChild(bloco);
            });
        }

        function avancarRodada() {
            var ultima = rodadaIndice + 1 >= rodadas.length;

            if (window.KidsProgresso) {
                window.KidsProgresso.bannerFase(container, ultima);
            }

            setTimeout(function () {
                rodadaIndice++;

                if (rodadaIndice < rodadas.length) {
                    renderizarRodada();
                } else if (window.KidsProgresso) {
                    window.KidsProgresso.liberarConclusao();
                }
            }, 1300);
        }

        atualizarEstrelas();
        renderizarRodada();
    }
})();
