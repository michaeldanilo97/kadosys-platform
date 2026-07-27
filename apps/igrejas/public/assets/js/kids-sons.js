/**
 * Efeitos sonoros do modo crianca - sintetizados via Web Audio API (sem
 * arquivos de audio pra baixar/hospedar). Carregado globalmente pelo
 * layout kids-app.php, disponibiliza window.KidsSons pros demais scripts
 * (kids-interacoes.js, kids-jogo-*.js) chamarem.
 */
(function () {
    'use strict';

    var CHAVE_MUDO = 'kids_som_mudo';
    var ctx = null;

    function contexto() {
        if (ctx) {
            return ctx;
        }

        var AudioContextClasse = window.AudioContext || window.webkitAudioContext;

        if (!AudioContextClasse) {
            return null;
        }

        ctx = new AudioContextClasse();

        return ctx;
    }

    function estaMudo() {
        return localStorage.getItem(CHAVE_MUDO) === '1';
    }

    function definirMudo(mudo) {
        localStorage.setItem(CHAVE_MUDO, mudo ? '1' : '0');
        atualizarBotao();
    }

    /**
     * Toca uma sequencia de tons puros (oscilador senoidal/quadrado) em
     * sucessao. `notas` e uma lista de { freq, duracao, atraso, tipo,
     * volume }, tudo em segundos exceto freq (Hz).
     */
    function tocarNotas(notas) {
        if (estaMudo()) {
            return;
        }

        var audio = contexto();

        if (!audio) {
            return;
        }

        if (audio.state === 'suspended') {
            audio.resume();
        }

        var agora = audio.currentTime;

        notas.forEach(function (nota) {
            var oscilador = audio.createOscillator();
            var ganho = audio.createGain();
            var inicio = agora + (nota.atraso || 0);
            var duracao = nota.duracao || 0.15;
            var volume = nota.volume != null ? nota.volume : 0.12;

            oscilador.type = nota.tipo || 'sine';
            oscilador.frequency.setValueAtTime(nota.freq, inicio);

            if (nota.freqFinal) {
                oscilador.frequency.exponentialRampToValueAtTime(nota.freqFinal, inicio + duracao);
            }

            ganho.gain.setValueAtTime(0.0001, inicio);
            ganho.gain.exponentialRampToValueAtTime(volume, inicio + 0.02);
            ganho.gain.exponentialRampToValueAtTime(0.0001, inicio + duracao);

            oscilador.connect(ganho);
            ganho.connect(audio.destination);

            oscilador.start(inicio);
            oscilador.stop(inicio + duracao + 0.02);
        });
    }

    var KidsSons = {
        estaMudo: estaMudo,

        alternarMudo: function () {
            definirMudo(!estaMudo());
        },

        clique: function () {
            tocarNotas([{ freq: 520, duracao: 0.06, tipo: 'triangle', volume: 0.08 }]);
        },

        virarCarta: function () {
            tocarNotas([{ freq: 380, duracao: 0.08, tipo: 'triangle', volume: 0.09 }]);
        },

        acerto: function () {
            tocarNotas([
                { freq: 523.25, duracao: 0.1, atraso: 0, tipo: 'sine' },
                { freq: 659.25, duracao: 0.1, atraso: 0.09, tipo: 'sine' },
                { freq: 783.99, duracao: 0.16, atraso: 0.18, tipo: 'sine' },
            ]);
        },

        erro: function () {
            tocarNotas([
                { freq: 220, duracao: 0.18, tipo: 'sawtooth', volume: 0.09, freqFinal: 140 },
            ]);
        },

        moeda: function () {
            tocarNotas([
                { freq: 987.77, duracao: 0.07, atraso: 0, tipo: 'square', volume: 0.07 },
                { freq: 1318.51, duracao: 0.12, atraso: 0.06, tipo: 'square', volume: 0.07 },
            ]);
        },

        fase: function () {
            tocarNotas([
                { freq: 659.25, duracao: 0.1, atraso: 0, tipo: 'sine' },
                { freq: 783.99, duracao: 0.1, atraso: 0.1, tipo: 'sine' },
                { freq: 987.77, duracao: 0.2, atraso: 0.2, tipo: 'sine' },
            ]);
        },

        notaSequencia: function (indice) {
            var notas = [523.25, 587.33, 659.25, 783.99, 880.0, 987.77];
            tocarNotas([{ freq: notas[indice % notas.length], duracao: 0.22, tipo: 'sine', volume: 0.09 }]);
        },

        ouvir: function () {
            tocarNotas([
                { freq: 440, duracao: 0.09, atraso: 0, tipo: 'sine', volume: 0.07 },
                { freq: 587.33, duracao: 0.14, atraso: 0.08, tipo: 'sine', volume: 0.07 },
            ]);
        },

        vitoria: function () {
            tocarNotas([
                { freq: 523.25, duracao: 0.12, atraso: 0, tipo: 'sine' },
                { freq: 659.25, duracao: 0.12, atraso: 0.11, tipo: 'sine' },
                { freq: 783.99, duracao: 0.12, atraso: 0.22, tipo: 'sine' },
                { freq: 1046.5, duracao: 0.3, atraso: 0.33, tipo: 'sine' },
            ]);
        },
    };

    var botaoMudo = null;

    function atualizarBotao() {
        if (!botaoMudo) {
            return;
        }

        var mudo = estaMudo();
        botaoMudo.textContent = mudo ? '🔇' : '🔊';
        botaoMudo.setAttribute('aria-label', mudo ? 'Ativar som' : 'Silenciar som');
        botaoMudo.setAttribute('aria-pressed', mudo ? 'true' : 'false');
    }

    function criarBotaoMudo() {
        if (document.querySelector('[data-kids-botao-mudo]')) {
            return;
        }

        botaoMudo = document.createElement('button');
        botaoMudo.type = 'button';
        botaoMudo.className = 'kids-botao-mudo';
        botaoMudo.setAttribute('data-kids-botao-mudo', '');

        botaoMudo.addEventListener('click', function () {
            KidsSons.alternarMudo();

            if (!estaMudo()) {
                KidsSons.clique();
            }
        });

        document.body.appendChild(botaoMudo);
        atualizarBotao();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', criarBotaoMudo);
    } else {
        criarBotaoMudo();
    }

    window.KidsSons = KidsSons;
})();
