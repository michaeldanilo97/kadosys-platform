(function () {
    'use strict';

    if (!('speechSynthesis' in window)) {
        // Sem suporte a sintese de voz no navegador (raro, mas possivel em
        // webviews antigos): esconde o botao em vez de deixa-lo sem efeito.
        var esconder = function () {
            document.querySelectorAll('[data-kids-ouvir]').forEach(function (botao) {
                botao.hidden = true;
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', esconder);
        } else {
            esconder();
        }

        return;
    }

    var vozes = [];
    var vozEscolhida = null;
    var falando = false;

    function pontuarVoz(voz) {
        var lang = (voz.lang || '').toLowerCase();

        if (lang.indexOf('pt') !== 0) {
            return -1;
        }

        var nome = (voz.name || '').toLowerCase();
        var pontos = 0;

        if (lang === 'pt-br') {
            pontos += 2;
        }

        if (!voz.localService) {
            // Vozes de rede (Google/Microsoft online) costumam soar bem
            // mais naturais que a voz offline padrao do sistema.
            pontos += 3;
        }

        if (nome.indexOf('google') !== -1 || nome.indexOf('microsoft') !== -1 || nome.indexOf('natural') !== -1) {
            pontos += 2;
        }

        return pontos;
    }

    function melhorVozPtBr() {
        var candidatas = vozes.filter(function (voz) { return pontuarVoz(voz) >= 0; });

        if (candidatas.length === 0) {
            return null;
        }

        candidatas.sort(function (a, b) { return pontuarVoz(b) - pontuarVoz(a); });

        return candidatas[0];
    }

    function atualizarVozes() {
        vozes = window.speechSynthesis.getVoices();
        vozEscolhida = melhorVozPtBr();
    }

    atualizarVozes();
    window.speechSynthesis.onvoiceschanged = atualizarVozes;

    function textoParaLeitura(botao) {
        var partes = [];
        var tituloEl = document.querySelector('[data-kids-ouvir-titulo]');
        var descricaoEl = document.querySelector('[data-kids-ouvir-descricao]');
        var alvoSeletor = botao.getAttribute('data-kids-ouvir');
        var alvoEl = alvoSeletor ? document.querySelector(alvoSeletor) : null;

        [tituloEl, descricaoEl, alvoEl].forEach(function (el) {
            if (el && el.textContent && el.textContent.trim()) {
                partes.push(el.textContent.trim());
            }
        });

        return partes.join('. ').replace(/\s+/g, ' ').trim();
    }

    function atualizarBotao(botao) {
        if (falando) {
            botao.classList.add('lendo');
            botao.innerHTML = '<i class="bi bi-stop-circle-fill"></i> Parar';
        } else {
            botao.classList.remove('lendo');
            botao.innerHTML = '<i class="bi bi-volume-up-fill"></i> Ouvir';
        }
    }

    function mascote() {
        return document.querySelector('[data-mascote]');
    }

    function pararLeitura() {
        falando = false;
        document.querySelectorAll('[data-kids-ouvir]').forEach(atualizarBotao);

        var widget = mascote();
        if (widget) {
            widget.classList.remove('kids-mascote-falando');
        }
    }

    function iniciar() {
        document.querySelectorAll('[data-kids-ouvir]').forEach(function (botao) {
            atualizarBotao(botao);

            botao.addEventListener('click', function () {
                if (falando) {
                    window.speechSynthesis.cancel();
                    pararLeitura();
                    return;
                }

                var texto = textoParaLeitura(botao);

                if (!texto) {
                    return;
                }

                window.speechSynthesis.cancel();

                var fala = new SpeechSynthesisUtterance(texto);
                fala.lang = 'pt-BR';
                fala.rate = 0.92;
                fala.pitch = 1.05;

                if (vozEscolhida) {
                    fala.voice = vozEscolhida;
                }

                fala.onend = pararLeitura;
                fala.onerror = pararLeitura;

                falando = true;
                atualizarBotao(botao);

                var widget = mascote();
                if (widget) {
                    widget.classList.add('kids-mascote-falando');
                }

                if (window.KidsSons) {
                    window.KidsSons.ouvir();
                }

                window.speechSynthesis.speak(fala);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }

    window.KidsLeitura = {
        parar: function () {
            window.speechSynthesis.cancel();
            pararLeitura();
        },
        estaFalando: function () { return falando; },
    };
})();
