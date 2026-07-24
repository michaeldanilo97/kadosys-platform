/**
 * Mascote nao-IA da Biblioteca Kids: widget flutuante presente em toda
 * pagina do modo crianca (ver resources/views/layouts/kids-app.php),
 * que reage com frases prontas escolhidas aleatoriamente deste arquivo -
 * nenhuma chamada de rede, nenhum modelo de linguagem, so um sorteio
 * simples dentro de listas fixas em PT-BR. O contexto inicial (parabens/
 * subiu de nivel/saudacao/parado) vem pronto do servidor via
 * data-mascote-contexto (ver o PHP do layout); o clique da propria
 * crianca sempre sorteia uma frase de incentivo, disponivel em
 * qualquer pagina.
 */
(function () {
    'use strict';

    var EMOJI_POR_MASCOTE = {
        leao: '🦁',
        ovelha: '🐑',
        pomba: '🕊️',
    };

    var FRASES = {
        saudacao: function (nome) {
            return [
                'Oi, ' + nome + '! Que bom te ver de novo! 👋',
                nome + ', bora aprender coisas incríveis hoje?',
                'Oba, ' + nome + ' chegou! Vamos nessa? ✨',
                'Hoje vai ser um dia especial, ' + nome + '!',
            ];
        },
        parabens: function () {
            return [
                'Parabéns! Você é demais! 🎉',
                'Muito bem! Continue assim! ⭐',
                'Uau, mandou muito bem nessa!',
                'Isso aí! Cada vitória conta! 💪',
            ];
        },
        nivel: function () {
            return [
                'Você subiu de nível! Incrível! 🏆',
                'Nível novo desbloqueado! Parabéns!',
                'Uau, você está cada vez mais forte na fé! 🌟',
            ];
        },
        incentivo: function () {
            return [
                'Vamos aprender mais um pouquinho? 📖',
                'Que tal um joguinho agora?',
                'Tenho orgulho de você! Continue explorando!',
                'Cada conteúdo te deixa mais perto de um troféu!',
                'Já viu a loja do Avatar? Tem coisas legais lá!',
            ];
        },
    };

    function sortear(lista) {
        return lista[Math.floor(Math.random() * lista.length)];
    }

    function iniciar() {
        var widget = document.querySelector('[data-mascote]');

        if (!widget) {
            return;
        }

        var figura = widget.querySelector('[data-mascote-figura]');
        var balao = widget.querySelector('[data-mascote-balao]');
        var emoji = EMOJI_POR_MASCOTE[widget.dataset.mascote] || EMOJI_POR_MASCOTE.leao;
        var nome = widget.dataset.mascotePrimeiroNome || '';
        var contexto = widget.dataset.mascoteContexto || 'parado';
        var escondeTimer = null;

        figura.textContent = emoji;

        function mostrarBalao(texto, comSom) {
            balao.textContent = texto;
            balao.hidden = false;
            widget.classList.add('kids-mascote-falando');

            if (comSom && window.KidsSons) {
                window.KidsSons.clique();
            }

            if (escondeTimer) {
                clearTimeout(escondeTimer);
            }

            escondeTimer = setTimeout(function () {
                balao.hidden = true;
                widget.classList.remove('kids-mascote-falando');
            }, 5000);
        }

        if (contexto === 'nivel') {
            mostrarBalao(sortear(FRASES.nivel()), false);
            if (window.KidsSons) {
                window.KidsSons.vitoria();
            }
        } else if (contexto === 'parabens') {
            mostrarBalao(sortear(FRASES.parabens()), false);
        } else if (contexto === 'saudacao') {
            mostrarBalao(sortear(FRASES.saudacao(nome)), false);
        }

        figura.addEventListener('click', function () {
            mostrarBalao(sortear(FRASES.incentivo()), true);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
