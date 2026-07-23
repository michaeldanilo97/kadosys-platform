-- KADOSYS Igrejas - Migracao 063
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids: converte os itens do tipo "atividade" que eram
-- so texto (pedindo pra fazer no papel ou "responder na tela" sem
-- nenhum mecanismo de verdade) em widgets realmente interativos:
-- "Complete o Versiculo" (preencher lacunas com correcao na hora),
-- "Ligue o Personagem a sua Historia" (jogo de ligar pares) e "Desenhe
-- a sua Oracao" (tela de desenho livre com canvas). O caca-palavras
-- "Personagens do Novo Testamento", que ja tinha virado redundante
-- desde que os caca-palavras de verdade (tipo jogo, migracao 059)
-- foram criados, vira um 4o puzzle de caca-palavras de verdade em vez
-- de continuar so texto.
--
-- Tambem precisa liberar 'atividade' na lista de tipos com HTML
-- confiavel em kids/show.php e dashboard/kids/biblioteca/show.php (ver
-- codigo da aplicacao) - sem isso o HTML abaixo apareceria escapado
-- (texto puro) em vez de renderizado.

DELETE FROM kids_conteudos WHERE tipo = 'atividade' AND origem = 'kadosys' AND titulo = 'Caça-palavras: Personagens do Novo Testamento';

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Caça-Nomes: Personagens do Novo Testamento', 'Ache os nomes de Pedro, João, Maria e outros personagens escondidos na grade de letras.', 'Novo Testamento', 'Personagens', NULL, NULL, 'facil', 6, 11, 10, 15, 8,
     '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/6 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(9, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">G</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="PEDRO">PEDRO</span>
<span class="kids-cp-palavra" data-cp-palavra="JOÃO">JOÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="MARIA">MARIA</span>
<span class="kids-cp-palavra" data-cp-palavra="TIAGO">TIAGO</span>
<span class="kids-cp-palavra" data-cp-palavra="JESUS">JESUS</span>
<span class="kids-cp-palavra" data-cp-palavra="PAULO">PAULO</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"PEDRO","cells":[[0,6],[1,6],[2,6],[3,6],[4,6]],"achada":false},{"word":"MARIA","cells":[[1,0],[2,0],[3,0],[4,0],[5,0]],"achada":false},{"word":"TIAGO","cells":[[4,5],[3,5],[2,5],[1,5],[0,5]],"achada":false},{"word":"JESUS","cells":[[4,2],[5,2],[6,2],[7,2],[8,2]],"achada":false},{"word":"PAULO","cells":[[7,4],[7,3],[7,2],[7,1],[7,0]],"achada":false},{"word":"JOÃO","cells":[[8,7],[7,7],[6,7],[5,7]],"achada":false}]</script>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-cacapalavras]'').forEach(function (jogo) {
        var dadosEl = jogo.querySelector(''[data-cp-dados]'');
        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector(''[data-cp-status]'');
        var total = palavras.length;
        var encontradas = 0;

        var ancora = null;
        var caminhoAtual = null;
        var ativo = false;
        var moveu = false;

        function celulaEm(r, c) {
            return jogo.querySelector(''[data-r="'' + r + ''"][data-c="'' + c + ''"]'');
        }

        function limparSelecao() {
            jogo.querySelectorAll(''.selecionada'').forEach(function (el) {
                el.classList.remove(''selecionada'');
            });
        }

        function marcarCaminho(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''selecionada'');
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
                    el.classList.add(''encontrada'');
                }
            });

            var chip = jogo.querySelector(''[data-cp-palavra="'' + palavra.word + ''"]'');

            if (chip) {
                chip.classList.add(''encontrada'');
            }

            if (status) {
                status.textContent = encontradas + ''/'' + total + '' encontradas'' + (encontradas === total ? '' - tudo achado! 🎉'' : '''');
            }
        }

        function flashErro(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''errada-tmp'');
                }
            });

            setTimeout(function () {
                caminho.forEach(function (pos) {
                    var el = celulaEm(pos[0], pos[1]);

                    if (el) {
                        el.classList.remove(''errada-tmp'');
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

        jogo.querySelectorAll(''[data-cp-celula]'').forEach(function (celula) {
            celula.addEventListener(''pointerdown'', function (event) {
                if (celula.classList.contains(''encontrada'')) {
                    return;
                }

                event.preventDefault();

                var r = parseInt(celula.getAttribute(''data-r''), 10);
                var c = parseInt(celula.getAttribute(''data-c''), 10);

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

        jogo.addEventListener(''pointermove'', function (event) {
            if (!ativo || !ancora) {
                return;
            }

            var alvo = document.elementFromPoint(event.clientX, event.clientY);
            var celula = alvo ? alvo.closest(''[data-cp-celula]'') : null;

            if (!celula || !jogo.contains(celula) || celula.classList.contains(''encontrada'')) {
                return;
            }

            moveu = true;

            var r = parseInt(celula.getAttribute(''data-r''), 10);
            var c = parseInt(celula.getAttribute(''data-c''), 10);

            atualizarCaminho(r, c);
        });

        document.addEventListener(''pointerup'', function () {
            if (!ativo) {
                return;
            }

            ativo = false;

            if (moveu) {
                finalizarSelecao();
            }
        });

        document.addEventListener(''pointercancel'', function () {
            ativo = false;
        });
    });
})();
</script>',
     NULL, 'publicado', NOW());

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-completar" data-completar>
<p class="kids-quiz-progresso" data-completar-progresso>0 de 4 certas</p>
<div class="kids-completar-item" data-completar-item data-resposta="pastor">
<p class="kids-completar-frase">1. "O Senhor é o meu <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false">, nada me faltará."</p>
<p class="kids-completar-ref">Salmos 23:1</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
<div class="kids-completar-item" data-completar-item data-resposta="fortalece">
<p class="kids-completar-frase">2. "Tudo posso naquele que me <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false">."</p>
<p class="kids-completar-ref">Filipenses 4:13</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
<div class="kids-completar-item" data-completar-item data-resposta="coração">
<p class="kids-completar-frase">3. "Tudo o que fizerem, façam de <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false">, como para o Senhor."</p>
<p class="kids-completar-ref">Colossenses 3:23</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
<div class="kids-completar-item" data-completar-item data-resposta="coração">
<p class="kids-completar-frase">4. "Confie no Senhor de todo o <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false">."</p>
<p class="kids-completar-ref">Provérbios 3:5</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
</div>
<script>
(function () {
    // O formulario de "Concluir" fica depois deste bloco no HTML (fora
    // do texto_conteudo), entao ainda nao existe no DOM quando este
    // <script> roda - so pode ser lido apos o documento terminar de
    // parsear (mesma correcao aplicada ao quiz, ver Ajuste 155).
    document.addEventListener(''DOMContentLoaded'', iniciar);

    function normalizar(texto) {
        return texto
            .normalize(''NFD'')
            .replace(/[\\u0300-\\u036f]/g, '''')
            .trim()
            .toLowerCase();
    }

    function iniciar() {
    document.querySelectorAll(''[data-completar]'').forEach(function (bloco) {
        var itens = bloco.querySelectorAll(''[data-completar-item]'');
        var progressoEl = bloco.querySelector(''[data-completar-progresso]'');
        var formConcluir = document.querySelector(''[data-quiz-form-concluir]'');
        var total = itens.length;
        var certas = 0;

        function atualizarProgresso() {
            if (progressoEl) {
                progressoEl.textContent = certas + '' de '' + total + '' certas'';
            }

            if (certas >= total && formConcluir) {
                formConcluir.hidden = false;
            }
        }

        itens.forEach(function (item) {
            var input = item.querySelector(''[data-completar-input]'');
            var botao = item.querySelector(''[data-completar-conferir]'');
            var feedback = item.querySelector(''[data-completar-feedback]'');
            var esperado = normalizar(item.getAttribute(''data-resposta''));
            var resolvido = false;

            function conferir() {
                if (resolvido) {
                    return;
                }

                var certo = normalizar(input.value) === esperado;
                feedback.hidden = false;
                feedback.classList.remove(''correta'', ''errada'');

                if (certo) {
                    feedback.classList.add(''correta'');
                    feedback.textContent = ''✅ Isso mesmo! A resposta é "'' + item.getAttribute(''data-resposta'') + ''".'';
                    input.disabled = true;
                    botao.disabled = true;
                    resolvido = true;
                    certas++;
                    atualizarProgresso();
                } else {
                    feedback.classList.add(''errada'');
                    feedback.textContent = ''❌ Quase! Tente de novo.'';
                }
            }

            botao.addEventListener(''click'', conferir);
            input.addEventListener(''keydown'', function (event) {
                if (event.key === ''Enter'') {
                    event.preventDefault();
                    conferir();
                }
            });
        });

        atualizarProgresso();
    });
    }
})();
</script>'
WHERE tipo = 'atividade' AND origem = 'kadosys' AND titulo = 'Complete o Versículo';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-ligar" data-ligar>
<p class="kids-quiz-progresso" data-ligar-progresso>0 de 5 pares certos</p>
<div class="kids-ligar-colunas">
<div class="kids-ligar-coluna">
<button type="button" class="kids-ligar-item" data-ligar-item data-par="noe" data-lado="esq">Noé</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="davi" data-lado="esq">Davi</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="jonas" data-lado="esq">Jonas</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="daniel" data-lado="esq">Daniel</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="moises" data-lado="esq">Moisés</button>
</div>
<div class="kids-ligar-coluna">
<button type="button" class="kids-ligar-item" data-ligar-item data-par="moises" data-lado="dir">abriu o Mar Vermelho</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="jonas" data-lado="dir">foi engolido por um peixe</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="noe" data-lado="dir">construiu uma arca</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="daniel" data-lado="dir">ficou na cova dos leões</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="davi" data-lado="dir">enfrentou um gigante</button>
</div>
</div>
</div>
<script>
(function () {
    // Mesma correcao do quiz (ver Ajuste 155): o form de "Concluir" vem
    // depois no HTML, entao so da pra ler ele depois do DOMContentLoaded.
    document.addEventListener(''DOMContentLoaded'', iniciar);

    function iniciar() {
    document.querySelectorAll(''[data-ligar]'').forEach(function (bloco) {
        var itens = bloco.querySelectorAll(''[data-ligar-item]'');
        var progressoEl = bloco.querySelector(''[data-ligar-progresso]'');
        var formConcluir = document.querySelector(''[data-quiz-form-concluir]'');
        var total = bloco.querySelectorAll(''[data-lado="esq"]'').length;
        var certos = 0;
        var selecionado = null;

        function atualizarProgresso() {
            if (progressoEl) {
                progressoEl.textContent = certos + '' de '' + total + '' pares certos'';
            }

            if (certos >= total && formConcluir) {
                formConcluir.hidden = false;
            }
        }

        function limparSelecao() {
            itens.forEach(function (item) {
                if (!item.classList.contains(''par-certo'')) {
                    item.classList.remove(''selecionado'');
                }
            });
            selecionado = null;
        }

        itens.forEach(function (item) {
            item.addEventListener(''click'', function () {
                if (item.classList.contains(''par-certo'')) {
                    return;
                }

                if (!selecionado) {
                    limparSelecao();
                    selecionado = item;
                    item.classList.add(''selecionado'');

                    return;
                }

                if (selecionado === item) {
                    limparSelecao();

                    return;
                }

                if (selecionado.getAttribute(''data-lado'') === item.getAttribute(''data-lado'')) {
                    limparSelecao();
                    selecionado = item;
                    item.classList.add(''selecionado'');

                    return;
                }

                if (selecionado.getAttribute(''data-par'') === item.getAttribute(''data-par'')) {
                    selecionado.classList.add(''par-certo'');
                    selecionado.classList.remove(''selecionado'');
                    item.classList.add(''par-certo'');
                    certos++;
                    atualizarProgresso();
                    selecionado = null;
                } else {
                    var errados = [selecionado, item];
                    errados.forEach(function (el) { el.classList.add(''par-errado''); });
                    setTimeout(function () {
                        errados.forEach(function (el) { el.classList.remove(''par-errado''); });
                    }, 350);
                    limparSelecao();
                }
            });
        });

        atualizarProgresso();
    });
    }
})();
</script>'
WHERE tipo = 'atividade' AND origem = 'kadosys' AND titulo = 'Ligue o Personagem à sua História';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-desenho" data-desenho>
<p class="kids-desenho-prompt">Desenhe algo pelo que você quer agradecer a Deus hoje, ou algo que você quer pedir a Ele em oração.</p>
<canvas class="kids-desenho-canvas" data-desenho-canvas width="360" height="280"></canvas>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#3A2E5C" style="background-color:#3A2E5C;" aria-label="Roxo escuro"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-limpar" data-desenho-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-desenho]'').forEach(function (palco) {
        var canvas = palco.querySelector(''[data-desenho-canvas]'');
        var ctx = canvas.getContext(''2d'');

        function preencherBranco() {
            ctx.fillStyle = ''#FFFFFF'';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

        preencherBranco();
        ctx.lineWidth = 6;
        ctx.lineCap = ''round'';
        ctx.lineJoin = ''round'';

        var corAtual = ''#3A2E5C'';
        var desenhando = false;
        var ultimo = null;

        function posicao(evento) {
            var rect = canvas.getBoundingClientRect();

            return {
                x: (evento.clientX - rect.left) * (canvas.width / rect.width),
                y: (evento.clientY - rect.top) * (canvas.height / rect.height),
            };
        }

        canvas.addEventListener(''pointerdown'', function (evento) {
            desenhando = true;
            ultimo = posicao(evento);
            canvas.setPointerCapture(evento.pointerId);
        });

        canvas.addEventListener(''pointermove'', function (evento) {
            if (!desenhando) {
                return;
            }

            var atual = posicao(evento);
            ctx.strokeStyle = corAtual;
            ctx.beginPath();
            ctx.moveTo(ultimo.x, ultimo.y);
            ctx.lineTo(atual.x, atual.y);
            ctx.stroke();
            ultimo = atual;
        });

        [''pointerup'', ''pointercancel'', ''pointerleave''].forEach(function (nomeEvento) {
            canvas.addEventListener(nomeEvento, function () {
                desenhando = false;
            });
        });

        palco.querySelectorAll(''[data-cor]'').forEach(function (botao) {
            botao.addEventListener(''click'', function () {
                palco.querySelectorAll(''[data-cor]'').forEach(function (b) { b.classList.remove(''ativa''); });
                botao.classList.add(''ativa'');
                corAtual = botao.getAttribute(''data-cor'');
            });
        });

        var limpar = palco.querySelector(''[data-desenho-limpar]'');

        if (limpar) {
            limpar.addEventListener(''click'', preencherBranco);
        }
    });
})();
</script>'
WHERE tipo = 'atividade' AND origem = 'kadosys' AND titulo = 'Desenhe a sua Oração';

