/**
 * Motor generico do jogo "classifique" (toque no item e depois na cesta
 * certa - equivalente a arrastar e soltar, mas com toque simples, mais
 * confiavel em celular/tablet do que drag-and-drop de verdade). Cada
 * conteudo declara `data-fases` (lista de fases, cada uma com
 * "instrucao", "zonas" e "itens"); o motor so avanca de fase quando
 * todos os itens da fase atual estiverem na cesta certa - encaixar
 * errado nao trava, o item so volta pra tentar de novo.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-jogo-arrastar]').forEach(iniciar);
    });

    function embaralhar(lista) {
        var copia = lista.slice();

        for (var i = copia.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = copia[i];
            copia[i] = copia[j];
            copia[j] = tmp;
        }

        return copia;
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

        var instrucaoEl = container.querySelector('[data-arrastar-instrucao]');
        var itensEl = container.querySelector('[data-arrastar-itens]');
        var zonasEl = container.querySelector('[data-arrastar-zonas]');

        if (!itensEl || !zonasEl) {
            return;
        }

        var faseIndice = 0;

        function renderizarFase() {
            var fase = fases[faseIndice];
            var itensRestantes = fase.itens.length;
            var selecionado = null;

            if (instrucaoEl) {
                instrucaoEl.textContent = fase.instrucao || 'Toque no item e depois na cesta certa!';
            }

            itensEl.innerHTML = '';
            zonasEl.innerHTML = '';

            fase.zonas.forEach(function (zona) {
                var cartaoZona = document.createElement('div');
                cartaoZona.className = 'kids-arrastar-zona';

                var emojiZona = document.createElement('span');
                emojiZona.className = 'kids-arrastar-zona-emoji';
                emojiZona.textContent = zona.emoji || '📦';
                cartaoZona.appendChild(emojiZona);

                var labelZona = document.createElement('span');
                labelZona.className = 'kids-arrastar-zona-label';
                labelZona.textContent = zona.label;
                cartaoZona.appendChild(labelZona);

                var colocadosZona = document.createElement('div');
                colocadosZona.className = 'kids-arrastar-zona-colocados';
                cartaoZona.appendChild(colocadosZona);

                cartaoZona.addEventListener('click', function () {
                    if (!selecionado) {
                        return;
                    }

                    var item = selecionado;
                    selecionado = null;
                    item.classList.remove('selecionado');

                    if (item.dataset.zona === zona.id) {
                        item.classList.add('correta');

                        setTimeout(function () {
                            item.classList.remove('correta');
                            item.classList.add('colocado');
                            item.disabled = true;
                            colocadosZona.appendChild(item);
                            itensRestantes--;

                            if (itensRestantes === 0) {
                                setTimeout(avancarFase, 500);
                            }
                        }, 350);
                    } else {
                        cartaoZona.classList.add('errada');

                        setTimeout(function () {
                            cartaoZona.classList.remove('errada');
                        }, 450);
                    }
                });

                zonasEl.appendChild(cartaoZona);
            });

            embaralhar(fase.itens).forEach(function (dadosItem) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'kids-arrastar-item';
                item.textContent = dadosItem.emoji;
                item.dataset.zona = dadosItem.zona;

                if (dadosItem.nome) {
                    item.setAttribute('aria-label', dadosItem.nome);
                }

                item.addEventListener('click', function () {
                    if (item.disabled) {
                        return;
                    }

                    if (selecionado === item) {
                        item.classList.remove('selecionado');
                        selecionado = null;
                        return;
                    }

                    if (selecionado) {
                        selecionado.classList.remove('selecionado');
                    }

                    item.classList.add('selecionado');
                    selecionado = item;
                });

                itensEl.appendChild(item);
            });
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
