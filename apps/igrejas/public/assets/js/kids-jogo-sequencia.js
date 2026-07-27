/**
 * Motor generico "sequencia magica" (estilo Genius/Simon): cada conteudo
 * declara `data-fases` (lista de fases, cada uma com "itens" - o
 * conjunto de emojis disponiveis - e "tamanho" - quantos emojis tem a
 * sequencia daquela fase). O motor sorteia uma sequencia aleatoria,
 * mostra ela piscando/tocando uma nota por emoji, e so libera os
 * botoes pra crianca repetir depois que a demonstracao termina. Errar
 * nao trava: a sequencia toca de novo automaticamente pra tentar outra
 * vez.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-jogo-sequencia]').forEach(iniciar);
    });

    function sortear(lista) {
        return lista[Math.floor(Math.random() * lista.length)];
    }

    function gerarSequencia(itens, tamanho) {
        var sequencia = [];

        for (var i = 0; i < tamanho; i++) {
            sequencia.push(sortear(itens));
        }

        return sequencia;
    }

    var INTERVALO_MS = 620;

    function iniciar(container) {
        var fases;

        try {
            fases = JSON.parse(container.getAttribute('data-fases'));
        } catch (erro) {
            fases = [];
        }

        if (!fases.length) {
            return;
        }

        var statusEl = container.querySelector('[data-sequencia-status]');
        var botoesEl = container.querySelector('[data-sequencia-botoes]');
        var repetirBtn = container.querySelector('[data-sequencia-repetir]');

        if (!botoesEl) {
            return;
        }

        var faseIndice = 0;

        function renderizarFase() {
            var fase = fases[faseIndice];
            var alvo = gerarSequencia(fase.itens, fase.tamanho);
            var posicao = 0;
            var bloqueado = true;
            var botoesPorEmoji = {};

            botoesEl.innerHTML = '';

            function desabilitarTudo() {
                Object.keys(botoesPorEmoji).forEach(function (emoji) {
                    botoesPorEmoji[emoji].disabled = true;
                });
            }

            function habilitarTudo() {
                Object.keys(botoesPorEmoji).forEach(function (emoji) {
                    botoesPorEmoji[emoji].disabled = false;
                });
            }

            function tocarSequencia() {
                bloqueado = true;
                desabilitarTudo();

                if (statusEl) {
                    statusEl.textContent = 'Fase ' + (faseIndice + 1) + ' de ' + fases.length + ' — observe a ordem! 👀';
                }

                alvo.forEach(function (emoji, indice) {
                    setTimeout(function () {
                        var botao = botoesPorEmoji[emoji];

                        if (!botao) {
                            return;
                        }

                        botao.classList.add('tocando');

                        if (window.KidsSons && window.KidsSons.notaSequencia) {
                            window.KidsSons.notaSequencia(indice);
                        }

                        setTimeout(function () {
                            botao.classList.remove('tocando');
                        }, 420);
                    }, indice * INTERVALO_MS);
                });

                setTimeout(function () {
                    bloqueado = false;
                    habilitarTudo();

                    if (statusEl) {
                        statusEl.textContent = 'Fase ' + (faseIndice + 1) + ' de ' + fases.length + ' — sua vez! Toque na mesma ordem 👆';
                    }
                }, alvo.length * INTERVALO_MS + 250);
            }

            fase.itens.forEach(function (emoji) {
                var botao = document.createElement('button');
                botao.type = 'button';
                botao.className = 'kids-sequencia-item';
                botao.textContent = emoji;
                botao.disabled = true;

                botao.addEventListener('click', function () {
                    if (bloqueado) {
                        return;
                    }

                    if (emoji === alvo[posicao]) {
                        botao.classList.add('correta');
                        setTimeout(function () { botao.classList.remove('correta'); }, 300);
                        posicao++;

                        if (posicao === alvo.length) {
                            bloqueado = true;
                            desabilitarTudo();
                            setTimeout(avancarFase, 700);
                        }
                    } else {
                        botao.classList.add('errada');
                        setTimeout(function () { botao.classList.remove('errada'); }, 400);
                        posicao = 0;
                        setTimeout(tocarSequencia, 700);
                    }
                });

                botoesPorEmoji[emoji] = botao;
                botoesEl.appendChild(botao);
            });

            if (repetirBtn) {
                repetirBtn.onclick = function () {
                    if (!bloqueado) {
                        tocarSequencia();
                    }
                };
            }

            tocarSequencia();
        }

        function avancarFase() {
            var ultima = faseIndice + 1 >= fases.length;

            if (window.KidsProgresso) {
                window.KidsProgresso.bannerFase(container, ultima);
            }

            setTimeout(function () {
                faseIndice++;

                if (faseIndice < fases.length) {
                    renderizarFase();
                } else if (window.KidsProgresso) {
                    window.KidsProgresso.liberarConclusao();
                }
            }, 1300);
        }

        renderizarFase();
    }
})();
