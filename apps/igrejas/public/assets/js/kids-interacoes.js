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

    document.addEventListener('DOMContentLoaded', function () {
        // Colorir: só libera o Concluir depois que a criança pintou pelo
        // menos uma vez cada regiao pintavel (".col") - sem isso dava pra
        // concluir uma pagina de colorir em branco.
        document.querySelectorAll('[data-colorir]').forEach(function (container) {
            var partes = container.querySelectorAll('.col');

            if (!partes.length) {
                return;
            }

            var pintadas = new Set();

            partes.forEach(function (parte, indice) {
                parte.addEventListener('click', function () {
                    pintadas.add(indice);

                    if (pintadas.size >= partes.length && window.KidsProgresso) {
                        window.KidsProgresso.liberarConclusao();
                    }
                });
            });
        });

        // Desenho livre (canvas): exige um traço de verdade (nao so um
        // toque) - contando movimentos com o botao/dedo pressionado.
        document.querySelectorAll('[data-desenho]').forEach(function (container) {
            var canvas = container.querySelector('[data-desenho-canvas]');

            if (!canvas) {
                return;
            }

            var movimentos = 0;
            var liberado = false;

            canvas.addEventListener('pointermove', function (evento) {
                if (liberado || evento.buttons !== 1) {
                    return;
                }

                movimentos++;

                if (movimentos > 12) {
                    liberado = true;

                    if (window.KidsProgresso) {
                        window.KidsProgresso.liberarConclusao();
                    }
                }
            });
        });

        // HQ: toca em cada quadrinho pra "virar a pagina" - so libera o
        // Concluir depois de passar por todos, senao dava pra concluir
        // sem ler a historia.
        document.querySelectorAll('.kids-hq').forEach(function (container) {
            var paineis = container.querySelectorAll('.kids-hq-painel');

            if (!paineis.length) {
                return;
            }

            var lidos = new Set();

            paineis.forEach(function (painel, indice) {
                painel.addEventListener('click', function () {
                    lidos.add(indice);
                    painel.classList.add('lido');

                    if (lidos.size >= paineis.length && window.KidsProgresso) {
                        window.KidsProgresso.liberarConclusao();
                    }
                });
            });
        });

        // Plano de leitura: checklist de dias - so libera com todos os
        // dias marcados como lidos.
        document.querySelectorAll('[data-plano-leitura]').forEach(function (container) {
            var dias = container.querySelectorAll('[data-plano-dia]');

            if (!dias.length) {
                return;
            }

            function verificar() {
                var todosMarcados = Array.prototype.every.call(dias, function (dia) {
                    return dia.checked;
                });

                if (todosMarcados && window.KidsProgresso) {
                    window.KidsProgresso.liberarConclusao();
                }
            }

            dias.forEach(function (dia) {
                dia.addEventListener('change', verificar);
            });
        });

        // Reacao (historia/devocional/versiculo ilustrado): a crianca
        // precisa escolher como o conteudo a fez sentir antes de concluir.
        document.querySelectorAll('[data-reacao]').forEach(function (container) {
            var opcoes = container.querySelectorAll('[data-reacao-opcao]');

            opcoes.forEach(function (opcao) {
                opcao.addEventListener('click', function () {
                    opcoes.forEach(function (o) { o.classList.remove('selecionada'); });
                    opcao.classList.add('selecionada');

                    if (window.KidsProgresso) {
                        window.KidsProgresso.liberarConclusao();
                    }
                });
            });
        });

        // PDF/arquivo: só libera o Concluir depois de realmente abrir o
        // arquivo (o link já abre em outra aba, entao nao perde o lugar).
        document.querySelectorAll('[data-pdf-abrir]').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.KidsProgresso) {
                    window.KidsProgresso.liberarConclusao();
                }
            });
        });

        // Video/audio: so libera o Concluir quando a midia termina de
        // tocar - assistir/ouvir de verdade e a acao exigida aqui.
        document.querySelectorAll('[data-midia-progresso]').forEach(function (midia) {
            midia.addEventListener('ended', function () {
                if (window.KidsProgresso) {
                    window.KidsProgresso.liberarConclusao();
                }
            });
        });

        // Desafio (foto da boa acao): so habilita o botao de enviar
        // depois que a criança escolhe/tira uma foto, com preview.
        document.querySelectorAll('[data-desafio-form]').forEach(function (form) {
            var input = form.querySelector('[data-desafio-input]');
            var preview = form.querySelector('[data-desafio-preview]');
            var enviar = form.querySelector('[data-desafio-enviar]');

            if (!input) {
                return;
            }

            input.addEventListener('change', function () {
                var arquivo = input.files && input.files[0];

                if (!arquivo) {
                    return;
                }

                if (enviar) {
                    enviar.disabled = false;
                }

                if (preview) {
                    var leitor = new FileReader();

                    leitor.onload = function () {
                        preview.src = String(leitor.result);
                        preview.hidden = false;
                    };

                    leitor.readAsDataURL(arquivo);
                }
            });
        });

        document.querySelectorAll('[data-slides]').forEach(function (container) {
            var slides = Array.prototype.slice.call(container.querySelectorAll('[data-slide]'));

            if (!slides.length) {
                return;
            }

            var contador = container.querySelector('[data-slide-contador]');
            var indice = Math.max(0, slides.findIndex(function (slide) {
                return slide.classList.contains('is-ativo');
            }));

            function mostrar(novoIndice) {
                slides[indice].classList.remove('is-ativo');
                indice = novoIndice;
                slides[indice].classList.add('is-ativo');

                if (contador) {
                    contador.textContent = (indice + 1) + ' / ' + slides.length;
                }

                if (indice === slides.length - 1 && window.KidsProgresso) {
                    window.KidsProgresso.liberarConclusao();
                }
            }

            var anterior = container.querySelector('[data-slide-prev]');
            var proxima = container.querySelector('[data-slide-next]');

            if (anterior) {
                anterior.addEventListener('click', function () {
                    mostrar((indice - 1 + slides.length) % slides.length);
                });
            }

            if (proxima) {
                proxima.addEventListener('click', function () {
                    mostrar((indice + 1) % slides.length);
                });
            }

            if (slides.length === 1 && window.KidsProgresso) {
                window.KidsProgresso.liberarConclusao();
            }
        });
    });
})();
