/**
 * Motor do caca-palavras (arrastar/tocar em linha reta pra marcar uma
 * palavra). Antes duplicado por inteiro em cada conteudo "jogo" (migracao
 * 061); agora um unico arquivo global, cada conteudo so guarda a grade
 * (letras + coordenadas) e a lista de palavras/celulas em
 * [data-cp-dados], sem repetir o algoritmo.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-cacapalavras]').forEach(iniciar);
    });

    function iniciar(jogo) {
        var dadosEl = jogo.querySelector('[data-cp-dados]');

        if (!dadosEl) {
            return;
        }

        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector('[data-cp-status]');
        var total = palavras.length;
        var encontradas = 0;

        var ancora = null;
        var caminhoAtual = null;
        var ativo = false;
        var moveu = false;

        function celulaEm(r, c) {
            return jogo.querySelector('[data-r="' + r + '"][data-c="' + c + '"]');
        }

        function limparSelecao() {
            jogo.querySelectorAll('.selecionada').forEach(function (el) {
                el.classList.remove('selecionada');
            });
        }

        function marcarCaminho(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add('selecionada');
                }
            });
        }

        function caminhoEntre(r1, c1, r2, c2) {
            var dr = r2 - r1;
            var dc = c2 - c1;
            var passos = Math.max(Math.abs(dr), Math.abs(dc));

            if (passos === 0) {
                return [[r1, c1]];
            }

            if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) {
                return null;
            }

            var stepR = dr === 0 ? 0 : dr / Math.abs(dr);
            var stepC = dc === 0 ? 0 : dc / Math.abs(dc);
            var caminho = [];

            for (var i = 0; i <= passos; i++) {
                caminho.push([r1 + stepR * i, c1 + stepC * i]);
            }

            return caminho;
        }

        function caminhosIguais(a, b) {
            if (a.length !== b.length) {
                return false;
            }

            var direto = a.every(function (p, i) { return p[0] === b[i][0] && p[1] === b[i][1]; });
            var reverso = a.every(function (p, i) { return p[0] === b[b.length - 1 - i][0] && p[1] === b[b.length - 1 - i][1]; });

            return direto || reverso;
        }

        function achaPalavra(caminho) {
            var encontrada = null;

            palavras.forEach(function (p) {
                if (!p.achada && caminhosIguais(caminho, p.cells)) {
                    encontrada = p;
                }
            });

            return encontrada;
        }

        function marcarEncontrada(palavra, caminho) {
            palavra.achada = true;
            encontradas++;

            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add('encontrada');
                }
            });

            var chip = jogo.querySelector('[data-cp-palavra="' + palavra.word + '"]');

            if (chip) {
                chip.classList.add('encontrada');
            }

            if (status) {
                status.textContent = encontradas + '/' + total + ' encontradas' + (encontradas === total ? ' - tudo achado! 🎉' : '');
            }

            if (encontradas === total && window.KidsProgresso) {
                window.KidsProgresso.liberarConclusao();
            }
        }

        function flashErro(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add('errada-tmp');
                }
            });

            setTimeout(function () {
                caminho.forEach(function (pos) {
                    var el = celulaEm(pos[0], pos[1]);

                    if (el) {
                        el.classList.remove('errada-tmp');
                    }
                });
            }, 350);
        }

        function iniciarSelecao(r, c) {
            ancora = { r: r, c: c };
            caminhoAtual = [[r, c]];
            limparSelecao();
            marcarCaminho(caminhoAtual);
        }

        function atualizarCaminho(r, c) {
            var caminho = caminhoEntre(ancora.r, ancora.c, r, c);

            if (caminho) {
                caminhoAtual = caminho;
                limparSelecao();
                marcarCaminho(caminhoAtual);
            }
        }

        function cancelarSelecao() {
            ancora = null;
            caminhoAtual = null;
            limparSelecao();
        }

        function finalizarSelecao() {
            if (!ancora || !caminhoAtual || caminhoAtual.length < 2) {
                return;
            }

            var acertou = achaPalavra(caminhoAtual);

            if (acertou) {
                marcarEncontrada(acertou, caminhoAtual);
            } else {
                flashErro(caminhoAtual);
            }

            limparSelecao();
            ancora = null;
            caminhoAtual = null;
        }

        jogo.querySelectorAll('[data-cp-celula]').forEach(function (celula) {
            celula.addEventListener('pointerdown', function (event) {
                if (celula.classList.contains('encontrada')) {
                    return;
                }

                event.preventDefault();

                var r = parseInt(celula.getAttribute('data-r'), 10);
                var c = parseInt(celula.getAttribute('data-c'), 10);

                ativo = true;
                moveu = false;

                if (!ancora) {
                    iniciarSelecao(r, c);

                    return;
                }

                if (r === ancora.r && c === ancora.c) {
                    cancelarSelecao();

                    return;
                }

                atualizarCaminho(r, c);
                finalizarSelecao();
            });
        });

        jogo.addEventListener('pointermove', function (event) {
            if (!ativo || !ancora) {
                return;
            }

            var alvo = document.elementFromPoint(event.clientX, event.clientY);
            var celula = alvo ? alvo.closest('[data-cp-celula]') : null;

            if (!celula || !jogo.contains(celula) || celula.classList.contains('encontrada')) {
                return;
            }

            moveu = true;

            var r = parseInt(celula.getAttribute('data-r'), 10);
            var c = parseInt(celula.getAttribute('data-c'), 10);

            atualizarCaminho(r, c);
        });

        document.addEventListener('pointerup', function () {
            if (!ativo) {
                return;
            }

            ativo = false;

            if (moveu) {
                finalizarSelecao();
            }
        });

        document.addEventListener('pointercancel', function () {
            ativo = false;
        });
    }
})();
