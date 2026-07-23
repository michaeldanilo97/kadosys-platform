(function () {
    'use strict';

    // AudioContext so pode ser criado/retomado apos um gesto real do
    // usuario (clique) - por isso e criado no primeiro clique na pagina
    // (avancar um pedido ja conta) e reaproveitado depois pros beeps.
    var audioCtx = null;

    function desbloquearAudio() {
        if (audioCtx !== null) {
            return;
        }

        try {
            var Ctx = window.AudioContext || window.webkitAudioContext;
            audioCtx = new Ctx();
        } catch (erro) {
            audioCtx = null;
        }
    }

    function tocarBeep(frequencia, atraso) {
        return new Promise(function (resolve) {
            if (audioCtx === null) {
                resolve();

                return;
            }

            try {
                var inicio = audioCtx.currentTime + atraso;
                var oscilador = audioCtx.createOscillator();
                var ganho = audioCtx.createGain();

                oscilador.type = 'sine';
                oscilador.frequency.setValueAtTime(frequencia, inicio);
                ganho.gain.setValueAtTime(0.0001, inicio);
                ganho.gain.exponentialRampToValueAtTime(0.35, inicio + 0.02);
                ganho.gain.exponentialRampToValueAtTime(0.0001, inicio + 0.35);

                oscilador.connect(ganho);
                ganho.connect(audioCtx.destination);
                oscilador.start(inicio);
                oscilador.stop(inicio + 0.4);
                oscilador.onended = function () {
                    resolve();
                };
            } catch (erro) {
                resolve();
            }
        });
    }

    function tocarAlertaPedidoNovo() {
        return Promise.all([
            tocarBeep(880, 0),
            tocarBeep(1108, 0.22),
            tocarBeep(1318, 0.44),
        ]);
    }

    function formatarTempo(segundosTotais) {
        var horas = Math.floor(segundosTotais / 3600);
        var minutos = Math.floor((segundosTotais % 3600) / 60);
        var segundos = segundosTotais % 60;
        var doisDigitos = function (numero) { return String(numero).padStart(2, '0'); };

        return horas > 0
            ? doisDigitos(horas) + ':' + doisDigitos(minutos) + ':' + doisDigitos(segundos)
            : doisDigitos(minutos) + ':' + doisDigitos(segundos);
    }

    function atualizarTimers() {
        document.querySelectorAll('[data-pedido-card]').forEach(function (card) {
            var criadoEmMs = parseInt(card.getAttribute('data-created-ms'), 10);

            if (!criadoEmMs) {
                return;
            }

            var timerEl = card.querySelector('[data-timer]');

            if (!timerEl) {
                return;
            }

            var segundos = Math.max(0, Math.floor((Date.now() - criadoEmMs) / 1000));
            timerEl.textContent = formatarTempo(segundos);
            card.classList.toggle('producao-atrasado', segundos > 1200);
        });
    }

    function iniciarPolling() {
        var board = document.querySelector('[data-quadro-producao]');

        if (!board) {
            return;
        }

        var dadosUrl = board.getAttribute('data-dados-url');
        var conhecidos = {};

        (board.getAttribute('data-known-ids') || '').split(',').forEach(function (id) {
            if (id !== '') {
                conhecidos[parseInt(id, 10)] = true;
            }
        });

        var recarregando = false;

        var recarregar = function () {
            if (recarregando) {
                return;
            }

            recarregando = true;
            window.location.reload();
        };

        setInterval(function () {
            if (recarregando) {
                return;
            }

            fetch(dadosUrl, { headers: { Accept: 'application/json' } })
                .then(function (resposta) { return resposta.ok ? resposta.json() : null; })
                .then(function (payload) {
                    if (!payload || !Array.isArray(payload.pedidos)) {
                        return;
                    }

                    var temPedidoNovo = payload.pedidos.some(function (pedido) {
                        return !conhecidos[pedido.id];
                    });

                    if (temPedidoNovo) {
                        tocarAlertaPedidoNovo().then(recarregar).catch(recarregar);
                        setTimeout(recarregar, 3000);
                    }
                })
                .catch(function () {
                    // Falha de rede pontual - tenta de novo no proximo ciclo.
                });
        }, 15000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        atualizarTimers();
        setInterval(atualizarTimers, 1000);
        iniciarPolling();

        document.addEventListener('click', desbloquearAudio, { once: true });

        var botaoSom = document.querySelector('[data-habilitar-som]');

        if (botaoSom) {
            botaoSom.addEventListener('click', function () {
                desbloquearAudio();
                tocarBeep(880, 0);
                botaoSom.setAttribute('hidden', 'hidden');
            });
        }
    });
})();
