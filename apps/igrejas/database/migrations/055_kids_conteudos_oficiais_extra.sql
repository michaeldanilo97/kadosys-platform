-- KADOSYS Igrejas - Migracao 055
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids: amplia a biblioteca oficial (origem 'kadosys',
-- ver migracao 052) com mais conteudos, cobrindo os tipos que ainda
-- nao tinham nenhum exemplo (slide, hq, plano_leitura, pdf, atividade,
-- jogo) e adicionando variedade aos tipos ja existentes.

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('historia', 'kadosys', 'Moisés e o Mar Vermelho', 'Deus abre um caminho no mar pra livrar o povo de Israel do exército do Egito.', 'Velho Testamento', 'Confiança', 'Êxodo', 'Moisés', 'facil', 5, 10, 9, 15, 8,
     'O povo de Israel estava preso entre o mar e o exército do Egito, que vinha atrás deles. Moisés levantou o bastão, e Deus abriu um caminho seco no meio do Mar Vermelho! Todo o povo atravessou em segurança. Quando o exército do Egito tentou seguir, as águas voltaram e os protegeram para sempre daquele perigo. Deus sempre abre um caminho para quem confia nele.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'O Nascimento de Jesus', 'Em uma noite especial, em Belém, nasce o filho de Deus.', 'Novo Testamento', 'Esperança', 'Lucas', 'Jesus', 'facil', 3, 8, 7, 15, 8,
     'Há muito tempo, em uma cidade chamada Belém, Maria e José procuravam um lugar para passar a noite, mas só encontraram um estábulo. Foi ali que Jesus nasceu! Anjos anunciaram a boa notícia para pastores no campo, e uma estrela brilhante guiou sábios de terras distantes até o menino. Todos vieram adorar Jesus, o presente mais especial que Deus deu ao mundo.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'José e o Casaco Colorido', 'José é vendido pelos próprios irmãos, mas Deus transforma essa história em bênção.', 'Velho Testamento', 'Perdão', 'Gênesis', 'José', 'medio', 6, 11, 10, 15, 8,
     'José era o filho favorito de Jacó e ganhou um casaco muito colorido, o que deixou os irmãos com ciúmes. Eles o venderam como escravo, mas Deus estava com José em cada etapa da sua jornada, até ele se tornar um governador poderoso no Egito. Anos depois, José reencontrou os irmãos e, em vez de se vingar, os perdoou. "Vocês pensaram em me fazer mal, mas Deus transformou isso em bem", disse ele.',
     NULL, 'publicado', NOW()),

    ('video', 'kadosys', 'A Parábola do Filho Pródigo', 'Uma história de Jesus sobre um pai que ama e perdoa o filho que voltou pra casa.', 'Novo Testamento', 'Amor de Deus', 'Lucas', 'Jesus', 'medio', 6, 12, 6, 15, 8,
     'Adicione o link do vídeo em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),
    ('video', 'kadosys', 'Daniel na Cova dos Leões', 'Daniel confia em Deus mesmo diante do perigo e é protegido de forma incrível.', 'Velho Testamento', 'Coragem', 'Daniel', 'Daniel', 'facil', 5, 10, 5, 15, 8,
     'Adicione o link do vídeo em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),

    ('audio', 'kadosys', 'Salmo 23 narrado para crianças', 'Uma narração calma do Salmo do Bom Pastor, para ouvir antes de dormir.', 'Louvor', 'Cuidado de Deus', 'Salmos', NULL, 'facil', 3, 8, 4, 10, 5,
     'Adicione o arquivo de áudio em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),

    ('slide', 'kadosys', 'Como a Bíblia chegou até nós', 'Uma apresentação simples explicando quem escreveu a Bíblia e como ela foi guardada até hoje.', 'Geral', 'A Bíblia', NULL, NULL, 'medio', 7, 12, 8, 15, 8,
     'A Bíblia foi escrita por muitas pessoas diferentes, ao longo de centenas de anos, mas sempre inspiradas por Deus. Ela tem duas grandes partes: o Antigo Testamento (antes de Jesus nascer) e o Novo Testamento (depois). Foi cuidadosamente copiada e traduzida em milhares de idiomas, para que crianças no mundo inteiro pudessem conhecer a Palavra de Deus - inclusive você!',
     NULL, 'publicado', NOW()),

    ('hq', 'kadosys', 'As Aventuras de José no Egito', 'Em quadrinhos: a jornada de José, do poço até o palácio do Egito.', 'Velho Testamento', 'Perseverança', 'Gênesis', 'José', 'facil', 6, 11, 10, 15, 8,
     'Adicione o arquivo da HQ (PDF ou imagens) em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),

    ('devocional', 'kadosys', 'Ore antes de dormir', 'Um devocional curto para encerrar o dia agradecendo a Deus.', 'Valores', 'Oração', NULL, NULL, 'facil', 3, 7, 3, 10, 5,
     'Antes de fechar os olhinhos para dormir, que tal conversar com Deus? Você pode agradecer por algo bom que aconteceu hoje, pedir perdão se fez algo errado, e pedir que Ele cuide da sua família enquanto todos dormem. Deus está sempre acordado, cuidando de você a noite inteira!',
     NULL, 'publicado', NOW()),
    ('devocional', 'kadosys', 'Deus me fez especial', 'Um devocional sobre como cada criança foi criada de um jeito único por Deus.', 'Valores', 'Identidade', 'Salmos', NULL, 'facil', 4, 9, 3, 10, 5,
     'Sabia que não existe ninguém igual a você no mundo inteiro? Deus te criou com muito cuidado, do jeitinho que Ele quis - sua altura, sua cor, seu jeito de rir. O Salmo 139 diz que fomos feitos de um jeito maravilhoso! Você não precisa ser igual a ninguém, porque Deus já te fez perfeito do seu jeito.',
     NULL, 'publicado', NOW()),

    ('estudo', 'kadosys', 'O que é oração?', 'Um estudo simples sobre como conversar com Deus.', 'Geral', 'Oração', NULL, NULL, 'facil', 6, 11, 8, 15, 8,
     'Orar é simplesmente conversar com Deus - não precisa de palavras difíceis nem de uma hora certa. Você pode orar agradecendo, pedindo ajuda, contando como foi seu dia ou só dizendo que ama a Deus. Jesus ensinou uma oração modelo (o "Pai Nosso") para nos mostrar como falar com Deus com respeito e confiança, como quem fala com um pai que ama muito.',
     NULL, 'publicado', NOW()),

    ('plano_leitura', 'kadosys', '7 dias com os Salmos', 'Um versículo por dia, durante uma semana, para aprender com os Salmos.', 'Geral', 'Salmos', 'Salmos', NULL, 'facil', 5, 12, NULL, 20, 10,
     'Dia 1: Salmos 23:1 - "O Senhor é o meu pastor, nada me faltará."
Dia 2: Salmos 118:24 - "Este é o dia que o Senhor fez; alegremo-nos e regozijemo-nos nele."
Dia 3: Salmos 46:1 - "Deus é o nosso refúgio e fortaleza."
Dia 4: Salmos 100:5 - "O Senhor é bom; a sua misericórdia dura para sempre."
Dia 5: Salmos 121:2 - "O meu socorro vem do Senhor, que fez os céus e a terra."
Dia 6: Salmos 34:8 - "Provai e vede que o Senhor é bom."
Dia 7: Salmos 139:14 - "Eu te louvarei, porque de um modo assombroso e maravilhoso fui formado."',
     NULL, 'publicado', NOW()),

    ('quiz', 'kadosys', 'Quiz: Livros da Bíblia', 'Você conhece os primeiros livros da Bíblia?', 'Geral', 'A Bíblia', NULL, NULL, 'medio', 7, 12, 5, 20, 10,
     NULL,
     '[{"pergunta":"Qual é o primeiro livro da Bíblia?","alternativas":["Êxodo","Gênesis","Salmos","Mateus"],"correta":1},{"pergunta":"Quantos livros tem o Novo Testamento?","alternativas":["27","39","66","12"],"correta":0},{"pergunta":"Qual desses é um dos quatro Evangelhos?","alternativas":["Gênesis","Salmos","João","Rute"],"correta":2}]',
     'publicado', NOW()),
    ('quiz', 'kadosys', 'Quiz: Milagres de Jesus', 'Teste seus conhecimentos sobre os milagres que Jesus fez.', 'Novo Testamento', 'Milagres', NULL, 'Jesus', 'medio', 7, 12, 5, 20, 10,
     NULL,
     '[{"pergunta":"O que Jesus transformou água em, nas bodas de Caná?","alternativas":["Suco","Vinho","Leite","Mel"],"correta":1},{"pergunta":"Quantos pães Jesus usou para alimentar a multidão?","alternativas":["2","5","10","20"],"correta":1},{"pergunta":"Sobre o que Jesus andou, para mostrar seu poder?","alternativas":["Fogo","Nuvens","Água","Areia"],"correta":2}]',
     'publicado', NOW()),

    ('colorir', 'kadosys', 'Jesus abençoa as crianças', 'Desenho para colorir de Jesus recebendo as crianças com carinho.', 'Novo Testamento', 'Amor de Deus', 'Marcos', 'Jesus', 'facil', 3, 8, NULL, 10, 5,
     NULL, NULL, 'publicado', NOW()),
    ('colorir', 'kadosys', 'Davi e Golias', 'Desenho para colorir do momento em que Davi enfrenta o gigante.', 'Velho Testamento', 'Coragem', '1 Samuel', 'Davi', 'facil', 3, 8, NULL, 10, 5,
     NULL, NULL, 'publicado', NOW()),

    ('pdf', 'kadosys', 'Caderno de Atividades: Os 10 Mandamentos', 'Material para imprimir com atividades sobre os mandamentos.', 'Velho Testamento', 'Obediência', 'Êxodo', 'Moisés', 'medio', 6, 11, NULL, 15, 8,
     'Adicione o arquivo PDF em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),

    ('atividade', 'kadosys', 'Caça-palavras: Personagens do Novo Testamento', 'Encontre os nomes escondidos de Jesus, Pedro, João, Maria e outros.', 'Novo Testamento', 'Personagens', NULL, NULL, 'facil', 6, 11, 10, 15, 8,
     'Imprima ou responda na tela: procure os nomes PEDRO, JOÃO, MARIA, TIAGO, JESUS e PAULO escondidos entre as letras. Peça ajuda a um adulto se precisar!',
     NULL, 'publicado', NOW()),

    ('jogo', 'kadosys', 'Monte a Arca de Noé', 'Jogo de encaixar os animais aos pares dentro da arca.', 'Velho Testamento', 'Obediência', 'Gênesis', 'Noé', 'facil', 3, 7, 8, 15, 8,
     'Adicione o link ou arquivo do jogo em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),

    ('desafio', 'kadosys', 'Desafio: Decore um versículo', 'Escolha um versículo curto e decore com a ajuda da família.', 'Geral', 'Memorização', NULL, NULL, 'facil', 5, 12, NULL, 20, 10,
     'Escolha um dos versículos da Biblioteca e tente decorar até o fim da semana. Peça para alguém da sua família ouvir você recitando! No próximo culto, conte para o seu professor que você conseguiu.',
     NULL, 'publicado', NOW()),

    ('versiculo_ilustrado', 'kadosys', 'Salmo 23:1', 'O Senhor é o meu pastor - um dos versículos mais amados da Bíblia.', 'Velho Testamento', 'Cuidado de Deus', 'Salmos', NULL, 'facil', 3, 8, 2, 10, 5,
     '"O Senhor é o meu pastor; nada me faltará." Salmos 23:1',
     NULL, 'publicado', NOW()),
    ('versiculo_ilustrado', 'kadosys', 'Filipenses 4:13', 'Um versículo de força para os dias difíceis.', 'Novo Testamento', 'Força', 'Filipenses', NULL, 'facil', 5, 12, 2, 10, 5,
     '"Tudo posso naquele que me fortalece." Filipenses 4:13',
     NULL, 'publicado', NOW());
