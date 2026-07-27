-- Ajuste 185: mais jogos pro Kids com mecanicas novas (pedido do usuario
-- "pode por mais jogos, com funcionalidades diferentes") - 2 motores
-- genericos novos (kids-jogo-arrastar.js: toque e encaixe/classificar;
-- kids-jogo-sequencia.js: sequencia magica estilo Genius/Simon) e 4
-- conteudos novos usando eles, 2 de cada.

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Frutos do Espírito ou Obras da Carne?', 'Toque em cada item e depois na cesta certa - separe o que é Fruto do Espírito do que é Obra da Carne.', 'Novo Testamento', 'Frutos do Espírito', NULL, NULL, 'facil', 6, 11, 8, 15, 8,
     '<div class="kids-jogo-arrastar" data-jogo-arrastar data-fases=''[{"instrucao":"Toque no item e depois na cesta certa!","zonas":[{"id":"fruto","label":"Fruto do Espírito","emoji":"🍇"},{"id":"obra","label":"Obra da Carne","emoji":"⚡"}],"itens":[{"emoji":"❤️","zona":"fruto","nome":"Amor"},{"emoji":"😊","zona":"fruto","nome":"Alegria"},{"emoji":"🕊️","zona":"fruto","nome":"Paz"},{"emoji":"😡","zona":"obra","nome":"Ira"},{"emoji":"😤","zona":"obra","nome":"Inveja"},{"emoji":"⚔️","zona":"obra","nome":"Briga"}]},{"instrucao":"Mais alguns - toque e encaixe na cesta certa!","zonas":[{"id":"fruto","label":"Fruto do Espírito","emoji":"🍇"},{"id":"obra","label":"Obra da Carne","emoji":"⚡"}],"itens":[{"emoji":"🙏","zona":"fruto","nome":"Mansidão"},{"emoji":"⏳","zona":"fruto","nome":"Paciência"},{"emoji":"🤝","zona":"fruto","nome":"Bondade"},{"emoji":"🍸","zona":"obra","nome":"Bebedeira"},{"emoji":"🗣️","zona":"obra","nome":"Fofoca"},{"emoji":"😒","zona":"obra","nome":"Egoísmo"}]}]''>
<p class="kids-arrastar-instrucao" data-arrastar-instrucao></p>
<div class="kids-arrastar-itens" data-arrastar-itens></div>
<div class="kids-arrastar-zonas" data-arrastar-zonas></div>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Antigo Testamento ou Novo Testamento?', 'Toque em cada personagem e depois na estante certa - Antigo ou Novo Testamento?', 'Geral', 'Personagens da Bíblia', NULL, NULL, 'medio', 6, 11, 8, 20, 12,
     '<div class="kids-jogo-arrastar" data-jogo-arrastar data-fases=''[{"instrucao":"Toque no personagem e depois na estante certa!","zonas":[{"id":"AT","label":"Antigo Testamento","emoji":"📜"},{"id":"NT","label":"Novo Testamento","emoji":"✝️"}],"itens":[{"emoji":"🧔","zona":"AT","nome":"Moisés"},{"emoji":"👑","zona":"AT","nome":"Davi"},{"emoji":"🚢","zona":"AT","nome":"Noé"},{"emoji":"✨","zona":"NT","nome":"Jesus"},{"emoji":"📖","zona":"NT","nome":"Paulo"},{"emoji":"🗝️","zona":"NT","nome":"Pedro"}]},{"instrucao":"Mais personagens - toque e encaixe na estante certa!","zonas":[{"id":"AT","label":"Antigo Testamento","emoji":"📜"},{"id":"NT","label":"Novo Testamento","emoji":"✝️"}],"itens":[{"emoji":"⭐","zona":"AT","nome":"Abraão"},{"emoji":"🦁","zona":"AT","nome":"Daniel"},{"emoji":"💪","zona":"AT","nome":"Sansão"},{"emoji":"👩","zona":"NT","nome":"Maria"},{"emoji":"💧","zona":"NT","nome":"João Batista"},{"emoji":"🙌","zona":"NT","nome":"Lázaro"}]}]''>
<p class="kids-arrastar-instrucao" data-arrastar-instrucao></p>
<div class="kids-arrastar-itens" data-arrastar-itens></div>
<div class="kids-arrastar-zonas" data-arrastar-zonas></div>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Sequência Mágica: Símbolos da Fé', 'Observe a sequência de símbolos piscando e depois repita tocando na mesma ordem - vai ficando mais difícil a cada fase!', 'Geral', 'Símbolos da Fé', NULL, NULL, 'facil', 6, 11, 6, 15, 8,
     '<div class="kids-jogo-sequencia" data-jogo-sequencia data-fases=''[{"itens":["✝️","🕊️","⭐","📖","🙏","❤️"],"tamanho":3},{"itens":["✝️","🕊️","⭐","📖","🙏","❤️"],"tamanho":4},{"itens":["✝️","🕊️","⭐","📖","🙏","❤️"],"tamanho":5}]''>
<p class="kids-sequencia-status" data-sequencia-status></p>
<div class="kids-sequencia-botoes" data-sequencia-botoes></div>
<button type="button" class="kids-sequencia-repetir" data-sequencia-repetir data-som-proprio>🔁 Ouvir de novo</button>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Sequência Mágica: Milagres de Jesus', 'Observe a sequência de milagres de Jesus piscando e depois repita tocando na mesma ordem - vai ficando mais difícil a cada fase!', 'Novo Testamento', 'Milagres de Jesus', NULL, NULL, 'medio', 6, 11, 6, 20, 12,
     '<div class="kids-jogo-sequencia" data-jogo-sequencia data-fases=''[{"itens":["🐟","🍞","🚶","💧","👁️","🩹"],"tamanho":3},{"itens":["🐟","🍞","🚶","💧","👁️","🩹"],"tamanho":4},{"itens":["🐟","🍞","🚶","💧","👁️","🩹"],"tamanho":5}]''>
<p class="kids-sequencia-status" data-sequencia-status></p>
<div class="kids-sequencia-botoes" data-sequencia-botoes></div>
<button type="button" class="kids-sequencia-repetir" data-sequencia-repetir data-som-proprio>🔁 Ouvir de novo</button>
</div>',
     NULL, 'publicado', NOW());
