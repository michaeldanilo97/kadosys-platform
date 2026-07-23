/**
 * Infraestrutura universal do modo crianca, carregada globalmente:
 *
 * 1) window.KidsProgresso - helpers compartilhados pelos motores de jogo
 *    com niveis (kids-jogo-memoria.js, kids-jogo-trivia.js): mostrar o
 *    banner de "fase concluida" e liberar o botao de Concluir (os mesmos
 *    elementos [data-quiz-form-concluir]/[data-quiz-aviso-pendente] que
 *    ja existem em kids/show.php pra qualquer conteudo com gate).
 *
 * 2) Som de clique universal em botoes/links do conteudo, exceto nos que
 *    ja tem som proprio (marcados com [data-som-proprio] ou que fazem
 *    parte dos jogos, que disparam som via observer abaixo).
 *
 * 3) MutationObserver que percebe as classes de estado ja usadas pelos
 *    jogos/quiz existentes (virada, correta, errada, errada-tmp,
 *    encontrada) e toca o som certo - cobre os 97 conteudos oficiais que
 *    ja existiam antes deste arquivo, sem precisar editar nenhum deles.
 */
(function () {
    'use strict';

    var SELETORES_SOM_PROPRIO = '.kids-quiz-alternativa, .kids-memoria-carta, .kids-cp-celula, [data-som-proprio]';

    var SONS_POR_CLASSE = {
        virada: 'virarCarta',
        correta: 'acerto',
        errada: 'erro',
        'errada-tmp': 'erro',
        encontrada: 'acerto',
    };

    window.KidsProgresso = {
        bannerFase: function (container, ultimaFase) {
            var banner = document.createElement('div');
            banner.className = 'kids-fase-banner';
            banner.textContent = ultimaFase ? '🏆 Última fase concluída!' : '✨ Fase concluída! Preparando a próxima...';
            container.appendChild(banner);

            if (window.KidsSons) {
                window.KidsSons.fase();
            }

            setTimeout(function () {
                banner.remove();
            }, 1350);
        },

        liberarConclusao: function () {
            var form = document.querySelector('[data-quiz-form-concluir]');
            var aviso = document.querySelector('[data-quiz-aviso-pendente]');

            if (form) {
                form.hidden = false;
            }

            if (aviso) {
                aviso.hidden = true;
            }

            if (window.KidsSons) {
                window.KidsSons.vitoria();
            }
        },
    };

    document.addEventListener('click', function (event) {
        if (!window.KidsSons) {
            return;
        }

        var alvo = event.target.closest('button, a.kids-btn-concluir, a.kids-voltar');

        if (!alvo || alvo.closest(SELETORES_SOM_PROPRIO) || alvo.disabled) {
            return;
        }

        window.KidsSons.clique();
    });

    if (typeof MutationObserver === 'undefined') {
        return;
    }

    var observer = new MutationObserver(function (mutacoes) {
        if (!window.KidsSons) {
            return;
        }

        mutacoes.forEach(function (mutacao) {
            if (mutacao.attributeName !== 'class') {
                return;
            }

            var antes = (mutacao.oldValue || '').split(/\s+/);
            var depois = Array.prototype.slice.call(mutacao.target.classList);

            for (var i = 0; i < depois.length; i++) {
                var classe = depois[i];

                if (classe && antes.indexOf(classe) === -1 && SONS_POR_CLASSE[classe]) {
                    window.KidsSons[SONS_POR_CLASSE[classe]]();
                    break;
                }
            }
        });
    });

    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class'],
        attributeOldValue: true,
        subtree: true,
    });
})();
