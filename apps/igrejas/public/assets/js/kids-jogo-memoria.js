/**
 * Motor generico do jogo da memoria, com fases (niveis). Cada conteudo
 * so declara `data-fases` (lista de listas de emojis - um array por
 * fase); o motor cuida de embaralhar, renderizar a grade, checar pares e
 * so avancar de fase quando a fase atual estiver 100% encontrada. Reusa
 * as mesmas classes/marcacoes do jogo da memoria original (migracao 058)
 * pra herdar o CSS/som/animacao ja existentes sem duplicar nada.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-jogo-memoria]').forEach(iniciar);
    });

    function embaralhar(lista) {
        for (var i = lista.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = lista[i];
            lista[i] = lista[j];
            lista[j] = tmp;
        }

        return lista;
    }

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

        var grade = container.querySelector('[data-memoria-grade]');
        var status = container.querySelector('[data-memoria-status]');

        if (!grade) {
            return;
        }

        var faseIndice = 0;

        function renderizarFase() {
            var emojis = fases[faseIndice];
            var cartas = embaralhar(emojis.concat(emojis));
            var viradas = [];
            var travado = false;
            var encontradas = 0;

            grade.innerHTML = '';
            grade.style.gridTemplateColumns = 'repeat(' + Math.min(4, cartas.length) + ', 1fr)';

            if (status) {
                status.textContent = 'Fase ' + (faseIndice + 1) + ' de ' + fases.length + ' — encontre os pares! 🧠';
            }

            cartas.forEach(function (emoji) {
                var carta = document.createElement('button');
                carta.type = 'button';
                carta.className = 'kids-memoria-carta';
                carta.setAttribute('data-emoji', emoji);
                carta.textContent = '❓';

                carta.addEventListener('click', function () {
                    if (travado || carta.classList.contains('virada') || carta.classList.contains('encontrada')) {
                        return;
                    }

                    carta.classList.add('virando');

                    setTimeout(function () {
                        carta.textContent = carta.getAttribute('data-emoji');
                        carta.classList.remove('virando');
                        carta.classList.add('virada');
                    }, 150);

                    viradas.push(carta);

                    if (viradas.length === 2) {
                        travado = true;
                        avaliarPar();
                    }
                });

                grade.appendChild(carta);
            });

            function avaliarPar() {
                var a = viradas[0];
                var b = viradas[1];

                setTimeout(function () {
                    if (a.getAttribute('data-emoji') === b.getAttribute('data-emoji')) {
                        a.classList.add('encontrada');
                        b.classList.add('encontrada');
                        encontradas += 2;
                        viradas = [];
                        travado = false;

                        if (status) {
                            status.textContent = 'Fase ' + (faseIndice + 1) + ' de ' + fases.length + ' — ' + (encontradas / 2) + '/' + (cartas.length / 2) + ' pares 🧠';
                        }

                        if (encontradas === cartas.length) {
                            setTimeout(avancarFase, 500);
                        }
                    } else {
                        a.classList.add('errada-tmp');
                        b.classList.add('errada-tmp');

                        setTimeout(function () {
                            a.textContent = '❓';
                            b.textContent = '❓';
                            a.classList.remove('virada', 'errada-tmp');
                            b.classList.remove('virada', 'errada-tmp');
                            viradas = [];
                            travado = false;
                        }, 700);
                    }
                }, 350);
            }
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
