-- KADOSYS Igrejas - Migracao 059
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids: novo tipo de atividade real - caca-palavras
-- (achar nomes biblicos numa grade de letras, clicando na primeira e na
-- ultima letra da palavra). E dinamico de verdade: a grade e o
-- caminho de cada palavra sao gerados uma vez (ver script auxiliar,
-- nao versionado) e a interacao acontece toda no navegador, sem
-- nenhuma chamada ao servidor - mesmo padrao dos outros jogos ja
-- existentes (memoria, trivia). 3 puzzles nesta leva, cada um com 8
-- nomes/palavras temáticas.

-- Gerado por gerar_caca_palavras.php - nao editar a mao.
INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Caça-Nomes: Os 12 Discípulos', 'Ache o nome dos 12 discípulos escondidos na grade de letras.', 'Novo Testamento', 'Discípulos', 'Mateus', NULL, 'medio', 6, 12, 10, 15, 8,
     '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">N</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="PEDRO">PEDRO</span>
<span class="kids-cp-palavra" data-cp-palavra="ANDRÉ">ANDRÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="TIAGO">TIAGO</span>
<span class="kids-cp-palavra" data-cp-palavra="JOÃO">JOÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="FILIPE">FILIPE</span>
<span class="kids-cp-palavra" data-cp-palavra="TOMÉ">TOMÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="MATEUS">MATEUS</span>
<span class="kids-cp-palavra" data-cp-palavra="JUDAS">JUDAS</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"FILIPE","cells":[[5,3],[4,3],[3,3],[2,3],[1,3],[0,3]],"achada":false},{"word":"MATEUS","cells":[[8,1],[8,2],[8,3],[8,4],[8,5],[8,6]],"achada":false},{"word":"PEDRO","cells":[[1,4],[2,4],[3,4],[4,4],[5,4]],"achada":false},{"word":"ANDRÉ","cells":[[3,8],[4,7],[5,6],[6,5],[7,4]],"achada":false},{"word":"TIAGO","cells":[[9,4],[9,5],[9,6],[9,7],[9,8]],"achada":false},{"word":"JUDAS","cells":[[4,0],[3,0],[2,0],[1,0],[0,0]],"achada":false},{"word":"JOÃO","cells":[[1,2],[2,2],[3,2],[4,2]],"achada":false},{"word":"TOMÉ","cells":[[2,8],[3,7],[4,6],[5,5]],"achada":false}]</script>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-cacapalavras]'').forEach(function (jogo) {
        var dadosEl = jogo.querySelector(''[data-cp-dados]'');
        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector(''[data-cp-status]'');
        var total = palavras.length;
        var encontradas = 0;
        var inicio = null;

        function celulaEm(r, c) {
            return jogo.querySelector(''[data-r="'' + r + ''"][data-c="'' + c + ''"]'');
        }

        function limparSelecao() {
            jogo.querySelectorAll(''.selecionada'').forEach(function (el) {
                el.classList.remove(''selecionada'');
            });
        }

        function caminhoEntre(r1, c1, r2, c2) {
            var dr = r2 - r1;
            var dc = c2 - c1;
            var passos = Math.max(Math.abs(dr), Math.abs(dc));

            if (passos === 0) {
                return null;
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

        jogo.querySelectorAll(''[data-cp-celula]'').forEach(function (celula) {
            celula.addEventListener(''click'', function () {
                if (celula.classList.contains(''encontrada'')) {
                    return;
                }

                var r = parseInt(celula.getAttribute(''data-r''), 10);
                var c = parseInt(celula.getAttribute(''data-c''), 10);

                if (!inicio) {
                    limparSelecao();
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var caminho = caminhoEntre(inicio.r, inicio.c, r, c);
                limparSelecao();

                if (!caminho) {
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var acertou = null;

                palavras.forEach(function (p) {
                    if (!p.achada && caminhosIguais(caminho, p.cells)) {
                        acertou = p;
                    }
                });

                if (acertou) {
                    acertou.achada = true;
                    encontradas++;

                    caminho.forEach(function (pos) {
                        var el = celulaEm(pos[0], pos[1]);

                        if (el) {
                            el.classList.add(''encontrada'');
                        }
                    });

                    var chip = jogo.querySelector(''[data-cp-palavra="'' + acertou.word + ''"]'');

                    if (chip) {
                        chip.classList.add(''encontrada'');
                    }

                    if (status) {
                        status.textContent = encontradas + ''/'' + total + '' encontradas'' + (encontradas === total ? '' - tudo achado! 🎉'' : '''');
                    }
                }

                inicio = null;
            });
        });
    });
})();
</script>',
     NULL, 'publicado', NOW()),
    ('jogo', 'kadosys', 'Caça-Nomes: Heróis do Velho Testamento', 'Ache o nome de grandes heróis da fé escondidos na grade de letras.', 'Velho Testamento', 'Heróis da Fé', NULL, NULL, 'medio', 6, 12, 10, 15, 8,
     '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">E</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="NOÉ">NOÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="MOISÉS">MOISÉS</span>
<span class="kids-cp-palavra" data-cp-palavra="JOSUÉ">JOSUÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="DAVI">DAVI</span>
<span class="kids-cp-palavra" data-cp-palavra="ESTER">ESTER</span>
<span class="kids-cp-palavra" data-cp-palavra="DANIEL">DANIEL</span>
<span class="kids-cp-palavra" data-cp-palavra="SANSÃO">SANSÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="GIDEÃO">GIDEÃO</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"MOISÉS","cells":[[7,7],[6,6],[5,5],[4,4],[3,3],[2,2]],"achada":false},{"word":"DANIEL","cells":[[7,6],[7,5],[7,4],[7,3],[7,2],[7,1]],"achada":false},{"word":"SANSÃO","cells":[[0,9],[1,9],[2,9],[3,9],[4,9],[5,9]],"achada":false},{"word":"GIDEÃO","cells":[[5,8],[4,7],[3,6],[2,5],[1,4],[0,3]],"achada":false},{"word":"JOSUÉ","cells":[[6,0],[5,0],[4,0],[3,0],[2,0]],"achada":false},{"word":"ESTER","cells":[[7,2],[6,2],[5,2],[4,2],[3,2]],"achada":false},{"word":"DAVI","cells":[[9,4],[9,5],[9,6],[9,7]],"achada":false},{"word":"NOÉ","cells":[[0,8],[1,8],[2,8]],"achada":false}]</script>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-cacapalavras]'').forEach(function (jogo) {
        var dadosEl = jogo.querySelector(''[data-cp-dados]'');
        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector(''[data-cp-status]'');
        var total = palavras.length;
        var encontradas = 0;
        var inicio = null;

        function celulaEm(r, c) {
            return jogo.querySelector(''[data-r="'' + r + ''"][data-c="'' + c + ''"]'');
        }

        function limparSelecao() {
            jogo.querySelectorAll(''.selecionada'').forEach(function (el) {
                el.classList.remove(''selecionada'');
            });
        }

        function caminhoEntre(r1, c1, r2, c2) {
            var dr = r2 - r1;
            var dc = c2 - c1;
            var passos = Math.max(Math.abs(dr), Math.abs(dc));

            if (passos === 0) {
                return null;
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

        jogo.querySelectorAll(''[data-cp-celula]'').forEach(function (celula) {
            celula.addEventListener(''click'', function () {
                if (celula.classList.contains(''encontrada'')) {
                    return;
                }

                var r = parseInt(celula.getAttribute(''data-r''), 10);
                var c = parseInt(celula.getAttribute(''data-c''), 10);

                if (!inicio) {
                    limparSelecao();
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var caminho = caminhoEntre(inicio.r, inicio.c, r, c);
                limparSelecao();

                if (!caminho) {
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var acertou = null;

                palavras.forEach(function (p) {
                    if (!p.achada && caminhosIguais(caminho, p.cells)) {
                        acertou = p;
                    }
                });

                if (acertou) {
                    acertou.achada = true;
                    encontradas++;

                    caminho.forEach(function (pos) {
                        var el = celulaEm(pos[0], pos[1]);

                        if (el) {
                            el.classList.add(''encontrada'');
                        }
                    });

                    var chip = jogo.querySelector(''[data-cp-palavra="'' + acertou.word + ''"]'');

                    if (chip) {
                        chip.classList.add(''encontrada'');
                    }

                    if (status) {
                        status.textContent = encontradas + ''/'' + total + '' encontradas'' + (encontradas === total ? '' - tudo achado! 🎉'' : '''');
                    }
                }

                inicio = null;
            });
        });
    });
})();
</script>',
     NULL, 'publicado', NOW()),
    ('jogo', 'kadosys', 'Caça-Nomes: Frutos do Espírito', 'Ache os frutos do Espírito escondidos na grade de letras.', 'Novo Testamento', 'Frutos do Espírito', 'Gálatas', NULL, 'dificil', 7, 12, 12, 15, 8,
     '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(11, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="10">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="10">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">Z</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="10">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="10">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="10">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="10">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">Ê</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="10">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">Í</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="10">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="10">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="10">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="3">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="4">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="6">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="9">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="10">D</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="AMOR">AMOR</span>
<span class="kids-cp-palavra" data-cp-palavra="ALEGRIA">ALEGRIA</span>
<span class="kids-cp-palavra" data-cp-palavra="PAZ">PAZ</span>
<span class="kids-cp-palavra" data-cp-palavra="PACIÊNCIA">PACIÊNCIA</span>
<span class="kids-cp-palavra" data-cp-palavra="BONDADE">BONDADE</span>
<span class="kids-cp-palavra" data-cp-palavra="FIDELIDADE">FIDELIDADE</span>
<span class="kids-cp-palavra" data-cp-palavra="MANSIDÃO">MANSIDÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="DOMÍNIO">DOMÍNIO</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"FIDELIDADE","cells":[[0,9],[1,9],[2,9],[3,9],[4,9],[5,9],[6,9],[7,9],[8,9],[9,9]],"achada":false},{"word":"PACIÊNCIA","cells":[[2,0],[3,1],[4,2],[5,3],[6,4],[7,5],[8,6],[9,7],[10,8]],"achada":false},{"word":"MANSIDÃO","cells":[[2,1],[3,2],[4,3],[5,4],[6,5],[7,6],[8,7],[9,8]],"achada":false},{"word":"ALEGRIA","cells":[[8,10],[7,10],[6,10],[5,10],[4,10],[3,10],[2,10]],"achada":false},{"word":"BONDADE","cells":[[7,7],[6,6],[5,5],[4,4],[3,3],[2,2],[1,1]],"achada":false},{"word":"DOMÍNIO","cells":[[4,1],[5,1],[6,1],[7,1],[8,1],[9,1],[10,1]],"achada":false},{"word":"AMOR","cells":[[4,5],[3,4],[2,3],[1,2]],"achada":false},{"word":"PAZ","cells":[[2,7],[2,6],[2,5]],"achada":false}]</script>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-cacapalavras]'').forEach(function (jogo) {
        var dadosEl = jogo.querySelector(''[data-cp-dados]'');
        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector(''[data-cp-status]'');
        var total = palavras.length;
        var encontradas = 0;
        var inicio = null;

        function celulaEm(r, c) {
            return jogo.querySelector(''[data-r="'' + r + ''"][data-c="'' + c + ''"]'');
        }

        function limparSelecao() {
            jogo.querySelectorAll(''.selecionada'').forEach(function (el) {
                el.classList.remove(''selecionada'');
            });
        }

        function caminhoEntre(r1, c1, r2, c2) {
            var dr = r2 - r1;
            var dc = c2 - c1;
            var passos = Math.max(Math.abs(dr), Math.abs(dc));

            if (passos === 0) {
                return null;
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

        jogo.querySelectorAll(''[data-cp-celula]'').forEach(function (celula) {
            celula.addEventListener(''click'', function () {
                if (celula.classList.contains(''encontrada'')) {
                    return;
                }

                var r = parseInt(celula.getAttribute(''data-r''), 10);
                var c = parseInt(celula.getAttribute(''data-c''), 10);

                if (!inicio) {
                    limparSelecao();
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var caminho = caminhoEntre(inicio.r, inicio.c, r, c);
                limparSelecao();

                if (!caminho) {
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var acertou = null;

                palavras.forEach(function (p) {
                    if (!p.achada && caminhosIguais(caminho, p.cells)) {
                        acertou = p;
                    }
                });

                if (acertou) {
                    acertou.achada = true;
                    encontradas++;

                    caminho.forEach(function (pos) {
                        var el = celulaEm(pos[0], pos[1]);

                        if (el) {
                            el.classList.add(''encontrada'');
                        }
                    });

                    var chip = jogo.querySelector(''[data-cp-palavra="'' + acertou.word + ''"]'');

                    if (chip) {
                        chip.classList.add(''encontrada'');
                    }

                    if (status) {
                        status.textContent = encontradas + ''/'' + total + '' encontradas'' + (encontradas === total ? '' - tudo achado! 🎉'' : '''');
                    }
                }

                inicio = null;
            });
        });
    });
})();
</script>',
     NULL, 'publicado', NOW());
