-- Migracao 064: modo crianca com mais conteudo, niveis, som e forca de
-- conclusao (ver kids-sons.js, kids-jogo-memoria.js, kids-jogo-trivia.js,
-- kids-jogo-cacapalavras.js e kids-interacoes.js em public/assets/js/).
--
-- Parte 1: retrofit dos jogos "jogo" existentes (que ate aqui nunca
-- tinham gate de conclusao - dava pra clicar Concluir sem jogar nada)
-- para os motores genericos com fases/rodadas, e consolidacao dos 3
-- caca-palavras (que tinham o algoritmo inteiro duplicado em cada linha)
-- no motor global kids-jogo-cacapalavras.js.

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-jogo-memoria" data-jogo-memoria data-fases=''[["🐘", "🦒", "🦁", "🐯"], ["🐘", "🦒", "🦁", "🐯", "🐻", "🐵"], ["🐘", "🦒", "🦁", "🐯", "🐻", "🐵", "🦓", "🦌"]]''>
<p class="kids-jogo-status" data-memoria-status>Fase 1 de 3 — encontre os pares! 🧠</p>
<div class="kids-memoria-grade" data-memoria-grade></div>
</div>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Monte a Arca de Noé';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-jogo-memoria" data-jogo-memoria data-fases=''[["📖", "✝️", "🕊️", "⭐"], ["📖", "✝️", "🕊️", "⭐", "🙏", "👑"], ["📖", "✝️", "🕊️", "⭐", "🙏", "👑", "🌈", "🐑"]]''>
<p class="kids-jogo-status" data-memoria-status>Fase 1 de 3 — encontre os pares! 🧠</p>
<div class="kids-memoria-grade" data-memoria-grade></div>
</div>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Memória Bíblica';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo": "Rodada 1: Aquecimento", "perguntas": [{"pergunta": "Quantos discípulos Jesus escolheu?", "alternativas": ["12", "7", "20", "3"], "correta": 0}, {"pergunta": "Quem escreveu muitos Salmos?", "alternativas": ["Davi", "Golias", "Faraó", "Herodes"], "correta": 0}, {"pergunta": "Em que cidade Jesus nasceu?", "alternativas": ["Belém", "Nazaré", "Jerusalém", "Roma"], "correta": 0}, {"pergunta": "Quem foi engolido por um grande peixe?", "alternativas": ["Jonas", "Pedro", "Paulo", "Elias"], "correta": 0}]}, {"titulo": "Rodada 2: Desafio Final", "perguntas": [{"pergunta": "Quantos dias Deus levou pra criar o mundo?", "alternativas": ["6", "3", "40", "10"], "correta": 0}, {"pergunta": "Quem atravessou o Mar Vermelho com o povo de Israel?", "alternativas": ["Moisés", "Josué", "Davi", "Sansão"], "correta": 0}, {"pergunta": "Qual é o primeiro livro da Bíblia?", "alternativas": ["Gênesis", "Êxodo", "Salmos", "Mateus"], "correta": 0}, {"pergunta": "Quem traiu Jesus por 30 moedas de prata?", "alternativas": ["Judas", "Pedro", "Tomé", "João"], "correta": 0}]}]''>
<div class="kids-corrida-cabecalho">
<span data-trivia-titulo>Rodada 1 de 2</span>
<span class="kids-corrida-estrelas" data-trivia-estrelas>⭐ 0/8</span>
</div>
<div data-trivia-perguntas></div>
</div>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Corrida da Fé';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-cacapalavras" data-cacapalavras>
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
</div>'
    WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Caça-Nomes: Os 12 Discípulos';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-cacapalavras" data-cacapalavras>
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
</div>'
    WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Caça-Nomes: Heróis do Velho Testamento';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-cacapalavras" data-cacapalavras>
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
</div>'
    WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Caça-Nomes: Frutos do Espírito';

-- Parte 2: conteudo novo usando os motores com niveis (memoria, trivia,
-- caca-palavras) + mais quizzes (o quiz ja tinha gate de conclusao
-- nativo desde antes, so precisava de mais variedade de temas).

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Memória: Milagres de Jesus', 'Jogo da memoria com 3 fases, dos pares faceis aos dificeis, sobre os milagres de Jesus.', 'Novo Testamento', 'Milagres', NULL, 'Jesus', 'medio', 5, 11, 6, 20, 12,
     '<div class="kids-jogo-memoria" data-jogo-memoria data-fases=''[["🐟", "🍞", "💧", "🍷"], ["🐟", "🍞", "💧", "🍷", "🌊", "👁️"], ["🐟", "🍞", "💧", "🍷", "🌊", "👁️", "🚶", "⚰️"]]''>
<p class="kids-jogo-status" data-memoria-status>Fase 1 de 3 — encontre os pares! 🧠</p>
<div class="kids-memoria-grade" data-memoria-grade></div>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Trilha da Criação', 'Trivia em 2 rodadas sobre os 7 dias da Criação - so avanca de rodada acertando tudo.', 'Velho Testamento', 'Criação', 'Gênesis', NULL, 'medio', 5, 11, 6, 20, 12,
     '<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo": "Rodada 1: Os primeiros dias", "perguntas": [{"pergunta": "O que Deus criou no 1º dia?", "alternativas": ["A luz", "Os animais", "O homem", "As plantas"], "correta": 0}, {"pergunta": "O que Deus criou no 2º dia?", "alternativas": ["O céu", "O sol", "Os peixes", "As aves"], "correta": 0}, {"pergunta": "No 3º dia, além da terra seca, o que mais Deus criou?", "alternativas": ["As plantas", "Os pássaros", "As estrelas", "O homem"], "correta": 0}, {"pergunta": "O que Deus criou no 4º dia?", "alternativas": ["Sol, lua e estrelas", "Os peixes", "Os répteis", "Adão"], "correta": 0}]}, {"titulo": "Rodada 2: A vida aparece", "perguntas": [{"pergunta": "O que Deus criou no 5º dia?", "alternativas": ["Peixes e aves", "Os animais terrestres", "O homem", "As montanhas"], "correta": 0}, {"pergunta": "Quem foi criado no 6º dia, junto com os animais?", "alternativas": ["O homem", "Os anjos", "As estrelas", "Os peixes"], "correta": 0}, {"pergunta": "O que Deus fez no 7º dia?", "alternativas": ["Descansou", "Criou o mar", "Criou o sol", "Criou Eva"], "correta": 0}, {"pergunta": "Em qual livro da Bíblia está a história da Criação?", "alternativas": ["Gênesis", "Êxodo", "Salmos", "Apocalipse"], "correta": 0}]}]''>
<div class="kids-corrida-cabecalho">
<span data-trivia-titulo>Rodada 1 de 2</span>
<span class="kids-corrida-estrelas" data-trivia-estrelas>⭐ 0/8</span>
</div>
<div data-trivia-perguntas></div>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Caça-Nomes: Mulheres da Bíblia', 'Encontre na grade os nomes de mulheres corajosas da Bíblia.', 'Geral', 'Mulheres da fé', NULL, NULL, 'medio', 6, 12, 8, 20, 12,
     '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">Z</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">Õ</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">Z</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">Í</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">Q</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">Q</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">Z</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">X</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">Ú</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">Õ</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">K</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">Y</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">Y</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">Í</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">Q</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">K</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">Í</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">Q</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">Ú</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">Ú</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">Q</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">Ú</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">X</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">K</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">Õ</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">X</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">T</button>
</div>
<div class="kids-cp-lista" data-cp-lista>
<span class="kids-cp-palavra" data-cp-palavra="ESTER">ESTER</span>
<span class="kids-cp-palavra" data-cp-palavra="RUTE">RUTE</span>
<span class="kids-cp-palavra" data-cp-palavra="SARA">SARA</span>
<span class="kids-cp-palavra" data-cp-palavra="MARIA">MARIA</span>
<span class="kids-cp-palavra" data-cp-palavra="ANA">ANA</span>
<span class="kids-cp-palavra" data-cp-palavra="DEBORA">DEBORA</span>
<span class="kids-cp-palavra" data-cp-palavra="REBECA">REBECA</span>
<span class="kids-cp-palavra" data-cp-palavra="RAQUEL">RAQUEL</span>
</div>
<script type="application/json" data-cp-dados>[{"word": "DEBORA", "cells": [[0, 4], [0, 5], [0, 6], [0, 7], [0, 8], [0, 9]], "achada": false}, {"word": "REBECA", "cells": [[3, 2], [4, 2], [5, 2], [6, 2], [7, 2], [8, 2]], "achada": false}, {"word": "RAQUEL", "cells": [[8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 6]], "achada": false}, {"word": "ESTER", "cells": [[3, 3], [3, 4], [3, 5], [3, 6], [3, 7]], "achada": false}, {"word": "MARIA", "cells": [[5, 5], [5, 6], [5, 7], [5, 8], [5, 9]], "achada": false}, {"word": "RUTE", "cells": [[1, 0], [2, 0], [3, 0], [4, 0]], "achada": false}, {"word": "SARA", "cells": [[4, 1], [5, 1], [6, 1], [7, 1]], "achada": false}, {"word": "ANA", "cells": [[0, 3], [1, 4], [2, 5]], "achada": false}]</script>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('quiz', 'kadosys', 'Quiz: Mulheres da Bíblia', 'Teste o que você sabe sobre as mulheres corajosas e cheias de fé da Bíblia!', 'Geral', 'Mulheres da fé', NULL, NULL, 'medio', 6, 12, 6, 20, 10,
     NULL,
     '[{"pergunta": "Quem foi a rainha que salvou seu povo arriscando a própria vida?", "alternativas": ["Ester", "Débora", "Rute", "Sara"], "correta": 0}, {"pergunta": "Qual mulher seguiu sua sogra Noemi e disse ''onde tu fores, irei''?", "alternativas": ["Rute", "Ana", "Raquel", "Rebeca"], "correta": 0}, {"pergunta": "Quem foi a mãe de Jesus?", "alternativas": ["Maria", "Marta", "Ana", "Eva"], "correta": 0}, {"pergunta": "Qual profetisa e juíza liderou Israel?", "alternativas": ["Débora", "Ester", "Sara", "Rute"], "correta": 0}, {"pergunta": "Quem foi a esposa de Abraão que teve um filho em idade avançada?", "alternativas": ["Sara", "Rebeca", "Raquel", "Lia"], "correta": 0}, {"pergunta": "Qual mulher orou muito por um filho e teve Samuel?", "alternativas": ["Ana", "Ester", "Débora", "Rute"], "correta": 0}]',
     'publicado', NOW()),
    ('quiz', 'kadosys', 'Quiz: Oração e Mandamentos', 'Um quiz sobre oração, os mandamentos e o maior ensino de Jesus.', 'Valores', 'Oração e obediência', NULL, 'Jesus', 'medio', 6, 12, 6, 20, 10,
     NULL,
     '[{"pergunta": "Qual é o mandamento mais importante segundo Jesus?", "alternativas": ["Amar a Deus sobre todas as coisas", "Não roubar", "Guardar o sábado", "Não mentir"], "correta": 0}, {"pergunta": "Quantos mandamentos Deus deu a Moisés no monte Sinai?", "alternativas": ["10", "7", "12", "5"], "correta": 0}, {"pergunta": "O que Jesus ensinou que devemos fazer com nossos inimigos?", "alternativas": ["Amar e orar por eles", "Ignorar", "Evitar", "Competir"], "correta": 0}, {"pergunta": "Qual oração Jesus ensinou aos discípulos?", "alternativas": ["Pai Nosso", "Ave Maria", "Salmo 23", "Credo"], "correta": 0}, {"pergunta": "Segundo a Bíblia, o que devemos fazer antes de pedir algo a Deus?", "alternativas": ["Agradecer e orar com fé", "Duvidar", "Desistir", "Nada"], "correta": 0}, {"pergunta": "Qual fruto do Espírito ajuda a esperar com calma?", "alternativas": ["Paciência", "Raiva", "Pressa", "Orgulho"], "correta": 0}]',
     'publicado', NOW());
