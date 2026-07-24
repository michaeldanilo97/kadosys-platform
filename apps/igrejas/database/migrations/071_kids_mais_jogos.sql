-- Ajuste 175: mais jogos pro Kids (pedido do usuario "pode acrescentar
-- mais jogos") - 2 novos jogos da memoria, 2 novas trivias/corridas e 2
-- novos caca-palavras, todos reaproveitando os 3 motores genericos ja
-- existentes (kids-jogo-memoria.js, kids-jogo-trivia.js,
-- kids-jogo-cacapalavras.js) - nenhum motor novo, so conteudo.

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Memória: A Criação do Mundo', 'Jogo da memória com 3 fases sobre os elementos que Deus criou em cada dia.', 'Antigo Testamento', 'Criação', NULL, NULL, 'medio', 6, 11, 10, 12, 6,
     '<div class="kids-jogo-memoria" data-jogo-memoria data-fases=''[["☀️","🌙","⭐","🌊"],["☀️","🌙","⭐","🌊","🐦","🐠"],["☀️","🌙","⭐","🌊","🐦","🐠","🌳","🧑"]]''>
<p class="kids-jogo-status" data-memoria-status>Fase 1 de 3 — encontre os pares! 🧠</p>
<div class="kids-memoria-grade" data-memoria-grade></div>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Memória: Parábolas de Jesus', 'Jogo da memória com 3 fases sobre os símbolos das parábolas que Jesus contou.', 'Novo Testamento', 'Parábolas', NULL, NULL, 'medio', 6, 11, 10, 12, 6,
     '<div class="kids-jogo-memoria" data-jogo-memoria data-fases=''[["🌾","💰","🐑","🏠"],["🌾","💰","🐑","🏠","🍷","🕯️"],["🌾","💰","🐑","🏠","🍷","🕯️","🌱","🚪"]]''>
<p class="kids-jogo-status" data-memoria-status>Fase 1 de 3 — encontre os pares! 🧠</p>
<div class="kids-memoria-grade" data-memoria-grade></div>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Desafio dos Profetas', 'Trivia em 2 rodadas sobre os grandes profetas da Bíblia - só avança acertando tudo.', 'Antigo Testamento', 'Profetas', NULL, NULL, 'medio', 6, 11, 10, 15, 8,
     '<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo":"Rodada 1: Grandes Profetas","perguntas":[{"pergunta":"Qual profeta foi levado ao céu em um redemoinho de fogo?","alternativas":["Elias","Eliseu","Isaías","Jeremias"],"correta":0},{"pergunta":"Quem interpretou sonhos para o rei da Babilônia?","alternativas":["Daniel","Ezequiel","Oséias","Joel"],"correta":0},{"pergunta":"Qual profeta foi jogado na cova dos leões?","alternativas":["Daniel","Jonas","Amós","Miquéias"],"correta":0},{"pergunta":"Quem fugiu de Deus e foi engolido por um grande peixe?","alternativas":["Jonas","Elias","Eliseu","Isaías"],"correta":0}]},{"titulo":"Rodada 2: Mensagens de Deus","perguntas":[{"pergunta":"Qual profeta anunciou o nascimento de Jesus em Belém?","alternativas":["Miquéias","Jonas","Daniel","Amós"],"correta":0},{"pergunta":"Quem viu uma escada entre a terra e o céu?","alternativas":["Jacó","Eliseu","Elias","Isaías"],"correta":0},{"pergunta":"Qual profeta multiplicou o azeite de uma viúva pobre?","alternativas":["Eliseu","Daniel","Jonas","Joel"],"correta":0},{"pergunta":"Quem confrontou os profetas de Baal no Monte Carmelo?","alternativas":["Elias","Eliseu","Daniel","Isaías"],"correta":0}]}]''>
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
    ('jogo', 'kadosys', 'Trivia dos Números da Bíblia', 'Trivia em 2 rodadas sobre números famosos das histórias bíblicas - só avança acertando tudo.', 'Geral', 'Curiosidades', NULL, NULL, 'medio', 6, 11, 10, 15, 8,
     '<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo":"Rodada 1: Numeros Famosos","perguntas":[{"pergunta":"Quantos dias e noites choveu no dilúvio?","alternativas":["40","7","100","12"],"correta":0},{"pergunta":"Quantas tribos de Israel existiam?","alternativas":["12","10","7","20"],"correta":0},{"pergunta":"Quantos mandamentos Deus deu a Moisés?","alternativas":["10","12","7","5"],"correta":0},{"pergunta":"Quantos discípulos Jesus escolheu?","alternativas":["12","10","7","3"],"correta":0}]},{"titulo":"Rodada 2: Mais Numeros","perguntas":[{"pergunta":"Quantos dias Jonas ficou na barriga do peixe?","alternativas":["3","7","1","40"],"correta":0},{"pergunta":"Em quantos dias Deus criou o mundo?","alternativas":["6","7","3","10"],"correta":0},{"pergunta":"Quantos anos os israelitas andaram no deserto?","alternativas":["40","12","7","100"],"correta":0},{"pergunta":"Quantos pães Jesus usou para alimentar a multidão?","alternativas":["5","2","12","7"],"correta":0}]}]''>
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
    ('jogo', 'kadosys', 'Caça-Nomes: Livros da Bíblia', 'Ache o nome de 8 livros da Bíblia escondidos na grade de letras.', 'Geral', 'Livros da Bíblia', NULL, NULL, 'medio', 6, 11, 10, 15, 8,
     '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">U</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="GENESIS">GENESIS</span>
<span class="kids-cp-palavra" data-cp-palavra="SALMOS">SALMOS</span>
<span class="kids-cp-palavra" data-cp-palavra="MATEUS">MATEUS</span>
<span class="kids-cp-palavra" data-cp-palavra="MARCOS">MARCOS</span>
<span class="kids-cp-palavra" data-cp-palavra="LUCAS">LUCAS</span>
<span class="kids-cp-palavra" data-cp-palavra="JOAO">JOAO</span>
<span class="kids-cp-palavra" data-cp-palavra="ATOS">ATOS</span>
<span class="kids-cp-palavra" data-cp-palavra="RUTE">RUTE</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"GENESIS","cells":[[9,7],[8,7],[7,7],[6,7],[5,7],[4,7],[3,7]],"achada":false},{"word":"SALMOS","cells":[[5,0],[4,1],[3,2],[2,3],[1,4],[0,5]],"achada":false},{"word":"MATEUS","cells":[[2,4],[2,5],[2,6],[2,7],[2,8],[2,9]],"achada":false},{"word":"MARCOS","cells":[[9,1],[8,2],[7,3],[6,4],[5,5],[4,6]],"achada":false},{"word":"LUCAS","cells":[[9,2],[8,3],[7,4],[6,5],[5,6]],"achada":false},{"word":"JOAO","cells":[[6,8],[5,8],[4,8],[3,8]],"achada":false},{"word":"ATOS","cells":[[8,1],[7,2],[6,3],[5,4]],"achada":false},{"word":"RUTE","cells":[[6,2],[5,3],[4,4],[3,5]],"achada":false}]</script>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Caça-Nomes: Lugares da Bíblia', 'Ache o nome de 8 cidades e lugares famosos da Bíblia escondidos na grade de letras.', 'Geral', 'Lugares da Bíblia', NULL, NULL, 'medio', 6, 11, 10, 15, 8,
     '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">Z</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">O</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="BELEM">BELEM</span>
<span class="kids-cp-palavra" data-cp-palavra="NAZARE">NAZARE</span>
<span class="kids-cp-palavra" data-cp-palavra="JERICO">JERICO</span>
<span class="kids-cp-palavra" data-cp-palavra="DAMASCO">DAMASCO</span>
<span class="kids-cp-palavra" data-cp-palavra="EGITO">EGITO</span>
<span class="kids-cp-palavra" data-cp-palavra="CANAA">CANAA</span>
<span class="kids-cp-palavra" data-cp-palavra="ROMA">ROMA</span>
<span class="kids-cp-palavra" data-cp-palavra="ATENAS">ATENAS</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"DAMASCO","cells":[[3,7],[4,7],[5,7],[6,7],[7,7],[8,7],[9,7]],"achada":false},{"word":"NAZARE","cells":[[0,3],[1,3],[2,3],[3,3],[4,3],[5,3]],"achada":false},{"word":"JERICO","cells":[[4,1],[4,2],[4,3],[4,4],[4,5],[4,6]],"achada":false},{"word":"ATENAS","cells":[[1,9],[2,9],[3,9],[4,9],[5,9],[6,9]],"achada":false},{"word":"BELEM","cells":[[5,4],[5,3],[5,2],[5,1],[5,0]],"achada":false},{"word":"EGITO","cells":[[8,1],[8,2],[8,3],[8,4],[8,5]],"achada":false},{"word":"CANAA","cells":[[0,5],[0,6],[0,7],[0,8],[0,9]],"achada":false},{"word":"ROMA","cells":[[6,4],[6,3],[6,2],[6,1]],"achada":false}]</script>
</div>',
     NULL, 'publicado', NOW());

