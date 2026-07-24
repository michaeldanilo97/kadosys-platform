-- Migracao 065: correcoes de qualidade no modo crianca (achadas na
-- revisao do Ajuste 168) - explicacao do quiz vazando a resposta antes
-- de responder (ver kids-biblioteca.css), 4o caca-palavras que ainda
-- nao tinha sido consolidado no motor global, 2 "jogos" que eram so
-- texto (viram widgets de verdade), 2 "jogos" que na real sao desafios
-- pra fazer em casa (tipo errado), e gate de conclusao pra slides.

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-cacapalavras" data-cacapalavras>
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
</div>'
    WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Caça-Nomes: Personagens do Novo Testamento';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo": "Rodada 1", "perguntas": [{"pergunta": "Jonas foi engolido por um grande peixe.", "alternativas": ["Verdadeiro", "Falso"], "correta": 0}, {"pergunta": "Davi enfrentou o gigante Golias com uma espada.", "alternativas": ["Verdadeiro", "Falso"], "correta": 1}, {"pergunta": "Jesus nasceu em Nazaré.", "alternativas": ["Verdadeiro", "Falso"], "correta": 1}]}, {"titulo": "Rodada 2", "perguntas": [{"pergunta": "Noé construiu uma arca para salvar sua família e os animais.", "alternativas": ["Verdadeiro", "Falso"], "correta": 0}, {"pergunta": "Moisés atravessou o Mar Vermelho a pé seco.", "alternativas": ["Verdadeiro", "Falso"], "correta": 0}, {"pergunta": "Judas foi um dos discípulos que traiu Jesus.", "alternativas": ["Verdadeiro", "Falso"], "correta": 0}]}]''>
<div class="kids-corrida-cabecalho">
<span data-trivia-titulo>Rodada 1 de 2</span>
<span class="kids-corrida-estrelas" data-trivia-estrelas>⭐ 0/6</span>
</div>
<div data-trivia-perguntas></div>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Verdadeiro ou Falso: Relâmpago Bíblico';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo": "Rodada 1", "perguntas": [{"pergunta": "Fui jogado em uma cova de leões por orar a Deus. Quem sou eu?", "alternativas": ["Daniel", "Davi", "Jonas", "Moisés"], "correta": 0}, {"pergunta": "Enfrentei um gigante chamado Golias com apenas uma funda. Quem sou eu?", "alternativas": ["Daniel", "Davi", "Sansão", "Josué"], "correta": 1}, {"pergunta": "Fui engolido por um grande peixe depois de fugir de Deus. Quem sou eu?", "alternativas": ["Noé", "Elias", "Jonas", "Pedro"], "correta": 2}]}, {"titulo": "Rodada 2", "perguntas": [{"pergunta": "Construí uma arca gigante por ordem de Deus. Quem sou eu?", "alternativas": ["Moisés", "Abraão", "Jonas", "Noé"], "correta": 3}, {"pergunta": "Sou o filho de Deus que nasceu em Belém e salvou o mundo. Quem sou eu?", "alternativas": ["João Batista", "Paulo", "Pedro", "Jesus"], "correta": 3}]}]''>
<div class="kids-corrida-cabecalho">
<span data-trivia-titulo>Rodada 1 de 2</span>
<span class="kids-corrida-estrelas" data-trivia-estrelas>⭐ 0/5</span>
</div>
<div data-trivia-perguntas></div>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Adivinhe o Personagem';

-- "Bingo dos Frutos do Espirito" e "Caca ao Tesouro Biblico em Casa" sao
-- atividades pra fazer em casa/offline, nao jogos digitais dentro do
-- app - o tipo 'jogo' cria expectativa de widget interativo que essas
-- duas nunca tiveram. 'desafio' e o tipo certo (mesmo padrao ja usado
-- em "Desafio da Semana: Ore por alguem").
UPDATE kids_conteudos SET tipo = 'desafio'
    WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Bingo dos Frutos do Espírito';

UPDATE kids_conteudos SET tipo = 'desafio'
    WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Caça ao Tesouro Bíblico em Casa';

-- "Como a Biblia chegou ate nos" era tipo=slide mas o conteudo nunca
-- virou de fato um slideshow - ficou texto puro sem nenhum HTML,
-- aparecendo como um paragrafo sem estilo nenhum na tela (essa e a
-- "Slides nao tem nada" que o usuario reportou). Convertido pro widget
-- kids-slides de verdade, igual aos outros 2 conteudos desse tipo.
UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-slides" data-slides>
<div class="kids-slide is-ativo" data-slide>
<span class="kids-slide-emoji">🖋️</span>
<h3>Muitos autores</h3>
<p>A Bíblia foi escrita por muitas pessoas diferentes, ao longo de centenas de anos, mas todas inspiradas por Deus.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">📜</span>
<h3>Antigo Testamento</h3>
<p>A primeira parte, escrita antes de Jesus nascer, conta a criação do mundo e a história do povo de Israel.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">✝️</span>
<h3>Novo Testamento</h3>
<p>A segunda parte, escrita depois de Jesus nascer, conta sua vida, seus ensinamentos e o começo da Igreja.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🌍</span>
<h3>Chegou até você!</h3>
<p>Foi copiada e traduzida em milhares de idiomas, pra que crianças no mundo inteiro pudessem conhecer a Palavra de Deus - inclusive você!</p>
</div>
<div class="kids-slides-nav">
<button type="button" class="kids-slides-btn" data-slide-prev aria-label="Anterior">‹</button>
<span class="kids-slides-contador" data-slide-contador>1 / 4</span>
<button type="button" class="kids-slides-btn" data-slide-next aria-label="Próxima">›</button>
</div>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'slide' AND titulo = 'Como a Bíblia chegou até nós';

-- Navegacao dos slides (prev/next) consolidada no kids-interacoes.js
-- global (agora tambem cuida do gate de conclusao) - remove o script
-- que estava duplicado em cada um dos 2 conteudos.
UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-slides" data-slides>
<div class="kids-slide is-ativo" data-slide>
<span class="kids-slide-emoji">🎣</span>
<h3>Pedro</h3>
<p>Era pescador antes de seguir Jesus. Se tornou um dos maiores líderes da igreja.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🐟</span>
<h3>André</h3>
<p>Irmão de Pedro - foi um dos primeiros a decidir seguir Jesus.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">⚡</span>
<h3>Tiago (filho de Zebedeu)</h3>
<p>Jesus o chamava, junto com o irmão João, de "filhos do trovão".</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">💌</span>
<h3>João</h3>
<p>O discípulo mais jovem do grupo. Escreveu um Evangelho e cartas cheias de amor.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🗺️</span>
<h3>Filipe</h3>
<p>Adorava apresentar outras pessoas a Jesus.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🌳</span>
<h3>Bartolomeu (Natanael)</h3>
<p>Jesus disse que o viu debaixo de uma figueira antes mesmo de chamá-lo.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">❓</span>
<h3>Tomé</h3>
<p>Ficou conhecido por duvidar até ver Jesus ressuscitado com os próprios olhos.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">💰</span>
<h3>Mateus</h3>
<p>Era cobrador de impostos antes de largar tudo para seguir Jesus.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🙏</span>
<h3>Tiago (filho de Alfeu)</h3>
<p>Um discípulo mais discreto, mas fiel até o fim.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">💛</span>
<h3>Tadeu</h3>
<p>Perguntou a Jesus como Ele se mostraria ao mundo.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🔥</span>
<h3>Simão, o Zelote</h3>
<p>Lutava por seu povo antes de aprender a lutar pelo Reino de Deus.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🗝️</span>
<h3>Judas Iscariotes</h3>
<p>Cuidava do dinheiro do grupo, mas depois traiu Jesus.</p>
</div>
<div class="kids-slides-nav">
<button type="button" class="kids-slides-btn" data-slide-prev aria-label="Anterior">‹</button>
<span class="kids-slides-contador" data-slide-contador>1 / 12</span>
<button type="button" class="kids-slides-btn" data-slide-next aria-label="Próxima">›</button>
</div>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'slide' AND titulo = 'Os 12 Discípulos de Jesus';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-slides" data-slides>
<div class="kids-slide is-ativo" data-slide>
<span class="kids-slide-emoji">🏙️</span>
<h3>Jerusalém</h3>
<p>A cidade mais importante da Bíblia - lá ficava o Templo, o coração da fé do povo de Israel.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🌊</span>
<h3>Mar da Galileia</h3>
<p>Um grande lago onde Jesus caminhou sobre as águas e chamou seus primeiros discípulos.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🐑</span>
<h3>Belém</h3>
<p>A pequena cidade onde Jesus nasceu, numa noite marcada por uma estrela.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🏡</span>
<h3>Nazaré</h3>
<p>A cidade onde Jesus cresceu, ao lado de Maria e José.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">💧</span>
<h3>Rio Jordão</h3>
<p>O rio onde João Batista batizava as pessoas - e onde Jesus também foi batizado.</p>
</div>
<div class="kids-slide" data-slide>
<span class="kids-slide-emoji">🐫</span>
<h3>Egito</h3>
<p>Para onde José foi levado, e para onde a família de Jesus fugiu quando Ele era bebê.</p>
</div>
<div class="kids-slides-nav">
<button type="button" class="kids-slides-btn" data-slide-prev aria-label="Anterior">‹</button>
<span class="kids-slides-contador" data-slide-contador>1 / 6</span>
<button type="button" class="kids-slides-btn" data-slide-next aria-label="Próxima">›</button>
</div>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'slide' AND titulo = 'Mapa da Terra Santa para Crianças';



-- Tipo 'estudo' nao tinha nenhuma acao exigida da crianca (so texto pra
-- ler). Adicionado ao allowlist de HTML confiavel (ver kids/show.php e
-- dashboard/kids/biblioteca/show.php) e incluida 1 pergunta rapida de
-- fixacao (motor de trivia, 1 pergunta) no fim de cada um dos 5 itens,
-- com o mesmo gate de conclusao dos outros widgets.

UPDATE kids_conteudos SET texto_conteudo = '<div class="texto">A Bíblia ensina que, quando deixamos o Espírito Santo agir em nossa vida, alguns "frutos" aparecem: amor, alegria, paz, paciência, amabilidade, bondade, fidelidade, mansidão e domínio próprio (Gálatas 5:22-23). Assim como uma árvore boa dá frutos bons, uma vida com Deus produz essas qualidades. Qual desses frutos você quer pedir a Deus para crescer mais em você esta semana?</div>
<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo": "Pergunta rápida", "perguntas": [{"pergunta": "Segundo Gálatas 5:22-23, os frutos do Espírito aparecem quando...", "alternativas": ["deixamos o Espírito Santo agir em nossa vida", "estudamos muito na escola", "ficamos mais velhos", "comemos frutas de verdade"], "correta": 0}]}]''>
<div class="kids-corrida-cabecalho">
<span data-trivia-titulo>Pergunta rápida — 1 de 1</span>
<span class="kids-corrida-estrelas" data-trivia-estrelas>⭐ 0/1</span>
</div>
<div data-trivia-perguntas></div>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'estudo' AND titulo = 'Os frutos do Espírito';

UPDATE kids_conteudos SET texto_conteudo = '<div class="texto">Orar é simplesmente conversar com Deus - não precisa de palavras difíceis nem de uma hora certa. Você pode orar agradecendo, pedindo ajuda, contando como foi seu dia ou só dizendo que ama a Deus. Jesus ensinou uma oração modelo (o "Pai Nosso") para nos mostrar como falar com Deus com respeito e confiança, como quem fala com um pai que ama muito.</div>
<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo": "Pergunta rápida", "perguntas": [{"pergunta": "Qual oração-modelo Jesus ensinou pra mostrar como falar com Deus?", "alternativas": ["O Pai Nosso", "O Salmo 23", "Os 10 Mandamentos", "O Credo"], "correta": 0}]}]''>
<div class="kids-corrida-cabecalho">
<span data-trivia-titulo>Pergunta rápida — 1 de 1</span>
<span class="kids-corrida-estrelas" data-trivia-estrelas>⭐ 0/1</span>
</div>
<div data-trivia-perguntas></div>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'estudo' AND titulo = 'O que é oração?';

UPDATE kids_conteudos SET texto_conteudo = '<div class="texto">Deus deu a Moisés dez regras importantes para o povo viver bem e em paz: amar somente a Deus, não fazer ídolos, respeitar o nome de Deus, descansar um dia por semana, honrar pai e mãe, não matar, ser fiel na família, não roubar, não mentir e não cobiçar o que é dos outros. Mais do que regras para seguir com medo, os 10 Mandamentos são um jeito de mostrar amor a Deus e às pessoas ao nosso redor.</div>
<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo": "Pergunta rápida", "perguntas": [{"pergunta": "A quem Deus deu os 10 Mandamentos?", "alternativas": ["Moisés", "Davi", "Jesus", "Abraão"], "correta": 0}]}]''>
<div class="kids-corrida-cabecalho">
<span data-trivia-titulo>Pergunta rápida — 1 de 1</span>
<span class="kids-corrida-estrelas" data-trivia-estrelas>⭐ 0/1</span>
</div>
<div data-trivia-perguntas></div>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'estudo' AND titulo = 'Os 10 Mandamentos explicados';

UPDATE kids_conteudos SET texto_conteudo = '<div class="texto">Quando o Espírito Santo vive em nós, Ele vai fazendo crescer certas características, como frutos em uma árvore: amor, alegria, paz, paciência, amabilidade, bondade, fidelidade, mansidão e domínio próprio. Ninguém nasce com esses frutos prontos - eles vão crescendo aos poucos, conforme nos aproximamos de Deus todos os dias, como uma árvore que precisa de água e sol para dar frutos bons.</div>
<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo": "Pergunta rápida", "perguntas": [{"pergunta": "Segundo o texto, como os frutos do Espírito crescem em nós?", "alternativas": ["Aos poucos, conforme nos aproximamos de Deus", "De uma vez, no dia do batismo", "Sozinhos, sem precisar de Deus", "Só quando ficamos adultos"], "correta": 0}]}]''>
<div class="kids-corrida-cabecalho">
<span data-trivia-titulo>Pergunta rápida — 1 de 1</span>
<span class="kids-corrida-estrelas" data-trivia-estrelas>⭐ 0/1</span>
</div>
<div data-trivia-perguntas></div>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'estudo' AND titulo = 'O Fruto do Espírito';

UPDATE kids_conteudos SET texto_conteudo = '<div class="texto">A Bíblia fala sobre a amizade de Davi e Jônatas, que se amavam como irmãos e cuidavam um do outro mesmo em momentos difíceis. Provérbios diz que um amigo ama em todo o tempo. Ser um bom amigo é ouvir quando o outro está triste, comemorar quando ele está feliz, falar a verdade com carinho e perdoar quando ele erra. Quem você pode ser um bom amigo hoje?</div>
<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo": "Pergunta rápida", "perguntas": [{"pergunta": "Quem eram os amigos que se amavam como irmãos, segundo a Bíblia?", "alternativas": ["Davi e Jônatas", "Pedro e João", "Moisés e Arão", "José e seus irmãos"], "correta": 0}]}]''>
<div class="kids-corrida-cabecalho">
<span data-trivia-titulo>Pergunta rápida — 1 de 1</span>
<span class="kids-corrida-estrelas" data-trivia-estrelas>⭐ 0/1</span>
</div>
<div data-trivia-perguntas></div>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'estudo' AND titulo = 'Como ser um bom amigo';
