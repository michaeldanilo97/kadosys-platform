-- KADOSYS Igrejas - Migracao 056
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids: segundo lote grande da biblioteca oficial
-- (origem 'kadosys', ver migracao 052), com foco pedido pela igreja
-- em mais historias, quiz e jogos, alem de reforcar os demais tipos
-- (colorir, versiculo ilustrado, devocional, estudo, atividade,
-- desafio, plano de leitura, pdf, hq, slide, video, audio).

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    -- Historias
    ('historia', 'kadosys', 'A Criação do Mundo', 'Deus cria os céus, a terra e tudo o que existe em seis dias.', 'Velho Testamento', 'Criação', 'Gênesis', NULL, 'facil', 3, 9, 8, 15, 8,
     'No começo, não havia nada além de Deus. Ele então criou a luz, o céu, os mares, a terra, as plantas, o sol, a lua e as estrelas. Depois, criou os peixes, os pássaros e todos os animais. Por fim, Deus criou o homem e a mulher à sua imagem, para cuidar de tudo o que Ele tinha feito. No sétimo dia, Deus descansou e olhou para tudo o que tinha criado: estava tudo muito bom!',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'Adão e Eva no Jardim do Éden', 'A primeira desobediência e o amor de Deus que não desiste.', 'Velho Testamento', 'Obediência', 'Gênesis', 'Adão e Eva', 'facil', 4, 9, 8, 15, 8,
     'Deus colocou Adão e Eva no lindo Jardim do Éden e disse que podiam comer de todas as árvores, menos de uma. Mas eles desobedeceram e comeram do fruto proibido. Por causa disso, tiveram que deixar o jardim. Mesmo assim, Deus continuou cuidando deles, porque o amor de Deus não desiste de ninguém, mesmo quando erramos.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'A Torre de Babel', 'O orgulho de um povo e uma lição sobre humildade.', 'Velho Testamento', 'União', 'Gênesis', NULL, 'medio', 6, 11, 7, 15, 8,
     'As pessoas resolveram construir uma torre bem alta para chegar até o céu, mais para mostrar orgulho do que para agradar a Deus. Então Deus fez com que elas passassem a falar línguas diferentes, e a construção parou porque ninguém mais se entendia. A história nos ensina que é melhor trabalharmos com humildade do que com orgulho.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'Abraão e a Terra Prometida', 'Deus chama Abraão para uma jornada de fé.', 'Velho Testamento', 'Fé', 'Gênesis', 'Abraão', 'medio', 6, 11, 9, 15, 8,
     'Deus pediu para Abraão deixar sua terra e ir para um lugar que Ele ainda ia mostrar, prometendo abençoá-lo e fazer dele uma grande nação. Sem saber exatamente para onde ia, Abraão confiou e obedeceu. Com o tempo, Deus cumpriu a promessa: Abraão se tornou pai de uma grande família, que existe até hoje. Deus sempre cumpre o que promete!',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'Rute, a Mulher Fiel', 'A fidelidade de Rute é recompensada por Deus.', 'Velho Testamento', 'Fidelidade', 'Rute', 'Rute', 'medio', 7, 12, 9, 15, 8,
     'Depois que ficou viúva, Rute poderia ter voltado para sua terra natal, mas escolheu ficar ao lado de sua sogra Noemi e cuidar dela com fidelidade. Trabalhando com honestidade nos campos, Rute foi notada por Boaz, um homem bondoso, que depois se casou com ela. A fidelidade de Rute foi recompensada por Deus, e ela se tornou parte da família de onde, gerações depois, nasceria o rei Davi.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'Ester, a Rainha Corajosa', 'Uma rainha usa sua coragem para salvar seu povo.', 'Velho Testamento', 'Coragem', 'Ester', 'Ester', 'medio', 7, 12, 10, 15, 8,
     'Ester se tornou rainha sem que ninguém soubesse que era judia, em um reino onde um homem mau planejava destruir o seu povo. Com coragem, mesmo correndo risco de vida, ela decidiu falar com o rei para salvar seu povo, dizendo: "Se eu tiver de morrer, morrerei". Deus usou a coragem de Ester para livrar todo o seu povo do perigo.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'Sansão e sua Força', 'A força de Deus através de um homem que aprendeu com seus erros.', 'Velho Testamento', 'Força de Deus', 'Juízes', 'Sansão', 'medio', 6, 11, 8, 15, 8,
     'Deus deu a Sansão uma força incrível para libertar o povo de Israel dos inimigos. Mas Sansão nem sempre fez escolhas sábias, e acabou perdendo sua força por um tempo. Mesmo assim, Deus não o abandonou, e no fim Sansão usou a força que Deus lhe deu mais uma vez para proteger o seu povo. A verdadeira força vem de confiar em Deus, e não só dos nossos músculos.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'Gideão e os 300 Guerreiros', 'Deus faz coisas grandes através de quem confia nele.', 'Velho Testamento', 'Confiança', 'Juízes', 'Gideão', 'medio', 7, 12, 9, 15, 8,
     'Deus escolheu Gideão, que se achava fraco e pequeno, para libertar Israel de um exército enorme. Para mostrar que a vitória viria dEle e não da força humana, Deus reduziu o exército de Gideão de milhares para apenas 300 homens. Com tochas, potes e trombetas, eles venceram o inimigo sem nem precisar lutar! Deus pode fazer coisas grandes através de quem confia nele, mesmo se sentindo pequeno.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'A Multiplicação dos Pães e Peixes', 'Jesus multiplica o pouco de um menino generoso.', 'Novo Testamento', 'Generosidade', 'João', 'Jesus', 'facil', 4, 10, 7, 15, 8,
     'Uma multidão seguiu Jesus e ficou até tarde, com fome, em um lugar deserto. Um menino ofereceu o pouco que tinha: cinco pães e dois peixinhos. Jesus abençoou aquela comida, e ela foi suficiente para alimentar mais de cinco mil pessoas, com muita sobra! Deus pode fazer coisas grandes com o pouco que oferecemos com um coração generoso.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'Zaqueu, o Homem Baixinho', 'Um encontro com Jesus muda o coração de um homem.', 'Novo Testamento', 'Transformação', 'Lucas', 'Zaqueu', 'facil', 5, 10, 7, 15, 8,
     'Zaqueu era um cobrador de impostos baixinho, que enganava as pessoas para ficar rico, e por isso ninguém gostava dele. Curioso para ver Jesus passar, ele subiu em uma árvore. Jesus olhou para cima, chamou Zaqueu pelo nome e foi almoçar na casa dele. Aquele encontro mudou o coração de Zaqueu, que decidiu devolver tudo o que tinha roubado. Jesus transforma qualquer coração que se abre para Ele.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'A Tempestade Acalmada', 'Jesus mostra que está sempre com a gente, mesmo nas tempestades.', 'Novo Testamento', 'Fé', 'Marcos', 'Jesus', 'facil', 5, 10, 6, 15, 8,
     'Os discípulos estavam em um barco quando uma tempestade forte começou, e eles ficaram com muito medo, achando que iam morrer. Jesus, que estava dormindo, acordou, olhou para o vento e disse: "Acalme-se!". Na mesma hora, tudo ficou calmo. Jesus perguntou por que eles tinham tanto medo, se tinham tão pouca fé. Ele está sempre com a gente, mesmo nas tempestades da vida.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'Lázaro Volta a Viver', 'Jesus mostra que tem poder até sobre a morte.', 'Novo Testamento', 'Poder de Deus', 'João', 'Lázaro', 'medio', 7, 12, 9, 15, 8,
     'Lázaro, amigo de Jesus, ficou doente e morreu. Quando Jesus chegou, já fazia quatro dias que ele tinha sido enterrado. Cheio de compaixão, Jesus foi até o túmulo e disse em voz alta: "Lázaro, saia!". E Lázaro saiu vivo, andando! Esse milagre mostrou que Jesus tem poder até sobre a morte, e que Ele é a ressurreição e a vida.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'A Ressurreição de Jesus', 'A maior vitória: Jesus venceu a morte.', 'Novo Testamento', 'Esperança', 'Mateus', 'Jesus', 'facil', 5, 12, 8, 15, 8,
     'Depois de morrer na cruz, Jesus foi colocado em um túmulo com uma grande pedra na entrada. No terceiro dia, algumas mulheres foram visitar o túmulo e o encontraram vazio! Um anjo disse: "Ele não está aqui, ressuscitou!". Jesus venceu a morte e apareceu vivo para seus discípulos. Por isso, na Páscoa, comemoramos que Jesus está vivo e cuida de nós todos os dias.',
     NULL, 'publicado', NOW()),
    ('historia', 'kadosys', 'Paulo na Estrada de Damasco', 'Deus transforma completamente o coração de um perseguidor.', 'Novo Testamento', 'Transformação', 'Atos', 'Paulo', 'medio', 8, 12, 9, 15, 8,
     'Saulo perseguia os cristãos porque não acreditava em Jesus. Um dia, no caminho para Damasco, uma luz forte do céu o cercou e ele ouviu a voz de Jesus perguntando por que o perseguia. Depois desse encontro, Saulo ficou cego por alguns dias, até ser curado e batizado, passando a se chamar Paulo. Ele se tornou um dos maiores anunciadores da boa notícia de Jesus. Deus pode transformar completamente qualquer coração.',
     NULL, 'publicado', NOW()),

    -- Quiz
    ('quiz', 'kadosys', 'Quiz: Heróis do Velho Testamento', 'Você reconhece esses nomes conhecidos da Bíblia?', 'Velho Testamento', 'Heróis da Fé', NULL, NULL, 'medio', 7, 12, 5, 20, 10,
     NULL,
     '[{"pergunta":"Quem construiu uma arca para salvar sua família e os animais?","alternativas":["Noé","Abraão","Moisés","Davi"],"correta":0},{"pergunta":"Quem enfrentou o gigante Golias com uma funda?","alternativas":["Sansão","Davi","Josué","Gideão"],"correta":1},{"pergunta":"Quem foi jogado na cova dos leões e não se machucou?","alternativas":["Daniel","José","Jonas","Elias"],"correta":0},{"pergunta":"Quem liderou o povo de Israel para fora do Egito?","alternativas":["Josué","Moisés","Abraão","Davi"],"correta":1}]',
     'publicado', NOW()),
    ('quiz', 'kadosys', 'Quiz: Parábolas de Jesus', 'O que você lembra das histórias que Jesus contava?', 'Novo Testamento', 'Parábolas', NULL, 'Jesus', 'medio', 7, 12, 5, 20, 10,
     NULL,
     '[{"pergunta":"Na parábola do Bom Samaritano, quem ajudou o homem ferido?","alternativas":["Um sacerdote","Um samaritano","Um levita","Um soldado"],"correta":1},{"pergunta":"Na parábola do filho pródigo, o que o pai fez quando o filho voltou?","alternativas":["Ficou bravo","Não deixou entrar","Correu para abraçá-lo","Mandou ele embora"],"correta":2},{"pergunta":"Na parábola da ovelha perdida, quantas ovelhas o pastor tinha ao todo?","alternativas":["10","50","100","1000"],"correta":2},{"pergunta":"O que a semente de mostarda representa na parábola de Jesus?","alternativas":["O Reino de Deus crescendo","Uma árvore grande","Um pássaro","Uma flor"],"correta":0}]',
     'publicado', NOW()),
    ('quiz', 'kadosys', 'Quiz: Os 12 Discípulos', 'Teste seus conhecimentos sobre os amigos mais próximos de Jesus.', 'Novo Testamento', 'Discípulos', NULL, NULL, 'medio', 7, 12, 5, 20, 10,
     NULL,
     '[{"pergunta":"Qual discípulo caminhou sobre a água com Jesus?","alternativas":["João","Tiago","Pedro","André"],"correta":2},{"pergunta":"Qual discípulo traiu Jesus?","alternativas":["Tomé","Judas","Filipe","Mateus"],"correta":1},{"pergunta":"Qual discípulo duvidou da ressurreição até ver Jesus com seus próprios olhos?","alternativas":["Tomé","Pedro","João","Bartolomeu"],"correta":0},{"pergunta":"Qual era a profissão de Mateus antes de seguir Jesus?","alternativas":["Pescador","Cobrador de impostos","Médico","Carpinteiro"],"correta":1}]',
     'publicado', NOW()),
    ('quiz', 'kadosys', 'Quiz: A Criação do Mundo', 'Você conhece a ordem em que Deus criou tudo?', 'Velho Testamento', 'Criação', 'Gênesis', NULL, 'facil', 5, 10, 5, 20, 10,
     NULL,
     '[{"pergunta":"Em que dia Deus criou o sol, a lua e as estrelas?","alternativas":["Dia 2","Dia 3","Dia 4","Dia 5"],"correta":2},{"pergunta":"Em que dia Deus descansou?","alternativas":["Dia 5","Dia 6","Dia 7","Dia 1"],"correta":2},{"pergunta":"O que Deus criou primeiro?","alternativas":["Os animais","A luz","O homem","As plantas"],"correta":1},{"pergunta":"De que Deus formou o primeiro homem?","alternativas":["Água","Pó da terra","Fogo","Madeira"],"correta":1}]',
     'publicado', NOW()),
    ('quiz', 'kadosys', 'Quiz: Frutos do Espírito', 'Quanto você sabe sobre os frutos citados em Gálatas 5?', 'Valores', 'Fruto do Espírito', 'Gálatas', NULL, 'medio', 7, 12, 5, 20, 10,
     NULL,
     '[{"pergunta":"Quantos frutos do Espírito são citados em Gálatas 5?","alternativas":["5","7","9","12"],"correta":2},{"pergunta":"Qual destes é um fruto do Espírito?","alternativas":["Inveja","Paciência","Orgulho","Preguiça"],"correta":1},{"pergunta":"Em qual livro da Bíblia encontramos a lista do Fruto do Espírito?","alternativas":["Romanos","Gálatas","Salmos","Atos"],"correta":1},{"pergunta":"Além do amor e da alegria, qual outro fruto começa com a letra P?","alternativas":["Fé","Paz","Bondade","Mansidão"],"correta":1}]',
     'publicado', NOW()),
    ('quiz', 'kadosys', 'Quiz: A Igreja Primitiva', 'O que aconteceu logo depois que Jesus subiu ao céu?', 'Novo Testamento', 'Igreja Primitiva', 'Atos', NULL, 'dificil', 8, 12, 5, 20, 10,
     NULL,
     '[{"pergunta":"O que aconteceu no dia de Pentecostes?","alternativas":["Choveu","O Espírito Santo desceu sobre os discípulos","Houve um terremoto","Jesus subiu ao céu"],"correta":1},{"pergunta":"Quem era Saulo antes de se tornar o apóstolo Paulo?","alternativas":["Um pescador","Um perseguidor dos cristãos","Um rei","Um sacerdote"],"correta":1},{"pergunta":"O que os primeiros cristãos faziam juntos, segundo Atos 2?","alternativas":["Brigavam por comida","Partilhavam tudo o que tinham","Escondiam suas coisas","Viviam sozinhos"],"correta":1},{"pergunta":"Quem foi o primeiro mártir cristão, apedrejado por sua fé?","alternativas":["Pedro","Estêvão","Filipe","Tiago"],"correta":1}]',
     'publicado', NOW()),
    ('quiz', 'kadosys', 'Quiz: Provérbios e Sabedoria', 'Teste o que você aprendeu sobre viver com sabedoria.', 'Geral', 'Sabedoria', 'Provérbios', NULL, 'medio', 7, 12, 5, 20, 10,
     NULL,
     '[{"pergunta":"Segundo Provérbios, o que é o começo da sabedoria?","alternativas":["Estudar muito","O temor do Senhor","Ser rico","Ter muitos amigos"],"correta":1},{"pergunta":"Provérbios ensina que devemos confiar no Senhor de...","alternativas":["Todo o coração","Vez em quando","Apenas nos domingos","Só quando precisamos"],"correta":0},{"pergunta":"Quem escreveu a maior parte do livro de Provérbios?","alternativas":["Davi","Salomão","Moisés","Paulo"],"correta":1},{"pergunta":"O que Provérbios diz sobre a resposta mansa?","alternativas":["Que afasta o furor","Que não serve pra nada","Que é sinal de fraqueza","Que ninguém entende"],"correta":0}]',
     'publicado', NOW()),
    ('quiz', 'kadosys', 'Quiz: Natal e Páscoa', 'Duas datas especiais para celebrar Jesus.', 'Novo Testamento', 'Datas Especiais', NULL, 'Jesus', 'facil', 5, 11, 5, 20, 10,
     NULL,
     '[{"pergunta":"Em que cidade Jesus nasceu?","alternativas":["Nazaré","Belém","Jerusalém","Jericó"],"correta":1},{"pergunta":"O que os pastores viram no céu anunciando o nascimento de Jesus?","alternativas":["Um cometa","Uma tempestade","Anjos","Um arco-íris"],"correta":2},{"pergunta":"O que comemoramos na Páscoa cristã?","alternativas":["O nascimento de Jesus","A ressurreição de Jesus","O batismo de Jesus","A subida ao céu"],"correta":1},{"pergunta":"Quantos dias depois de morrer Jesus ressuscitou?","alternativas":["1","2","3","7"],"correta":2}]',
     'publicado', NOW()),

    -- Jogos
    ('jogo', 'kadosys', 'Verdadeiro ou Falso: Relâmpago Bíblico', 'Responda rápido: será que é verdade ou mentira?', 'Geral', 'Conhecimentos Gerais', NULL, NULL, 'facil', 6, 12, 5, 15, 8,
     'Responda rápido: verdadeiro ou falso?
1. Jonas foi engolido por um grande peixe. (Verdadeiro)
2. Davi enfrentou o gigante Golias com uma espada. (Falso - ele usou uma funda e pedras)
3. Jesus nasceu em Nazaré. (Falso - Ele nasceu em Belém)
4. Noé construiu uma arca para salvar sua família e os animais. (Verdadeiro)
5. Moisés atravessou o Mar Vermelho a pé seco. (Verdadeiro)
6. Judas foi um dos discípulos que traiu Jesus. (Verdadeiro)
Conte quantas você acertou e desafie um amigo a jogar também!',
     NULL, 'publicado', NOW()),
    ('jogo', 'kadosys', 'Adivinhe o Personagem', 'Leia as dicas e descubra quem é o personagem bíblico.', 'Geral', 'Personagens', NULL, NULL, 'facil', 5, 11, 6, 15, 8,
     'Leia as dicas e tente adivinhar de quem estamos falando antes de olhar a resposta!
Dica 1: Fui jogado em uma cova de leões por orar a Deus. Quem sou eu? (Daniel)
Dica 2: Enfrentei um gigante chamado Golias com apenas uma funda. Quem sou eu? (Davi)
Dica 3: Fui engolido por um grande peixe depois de fugir de Deus. Quem sou eu? (Jonas)
Dica 4: Construí uma arca gigante por ordem de Deus. Quem sou eu? (Noé)
Dica 5: Sou o filho de Deus que nasceu em Belém e salvou o mundo. Quem sou eu? (Jesus)
Brinque com a família tentando criar novas dicas para outros personagens!',
     NULL, 'publicado', NOW()),
    ('jogo', 'kadosys', 'Bingo dos Frutos do Espírito', 'Um bingo divertido para aprender os 9 frutos do Espírito.', 'Valores', 'Fruto do Espírito', 'Gálatas', NULL, 'facil', 4, 10, 10, 15, 8,
     'Adicione a cartela para impressão em Kids > Conteúdos > Editar. Enquanto isso, brinque em casa: escreva os 9 frutos do Espírito (amor, alegria, paz, paciência, amabilidade, bondade, fidelidade, mansidão e domínio próprio) em pedacinhos de papel e sorteie um por vez, contando uma situação em que você pode praticar aquele fruto hoje.',
     NULL, 'publicado', NOW()),
    ('jogo', 'kadosys', 'Corrida da Fé', 'Um jogo de tabuleiro em família com desafios bíblicos.', 'Geral', 'Jornada de Fé', NULL, NULL, 'medio', 6, 12, 15, 15, 8,
     'Adicione o tabuleiro para impressão em Kids > Conteúdos > Editar. Regra simples enquanto isso: desenhe um caminho de casas numeradas no chão ou em uma folha, jogue um dado e avance. Em algumas casas, escreva desafios como "recite um versículo" ou "conte uma história da Bíblia" antes de continuar andando.',
     NULL, 'publicado', NOW()),
    ('jogo', 'kadosys', 'Memória Bíblica', 'Jogo da memória com personagens e histórias da Bíblia.', 'Geral', 'Personagens', NULL, NULL, 'facil', 4, 9, 8, 15, 8,
     'Adicione as cartas do jogo da memória em Kids > Conteúdos > Editar.',
     NULL, 'publicado', NOW()),
    ('jogo', 'kadosys', 'Caça ao Tesouro Bíblico em Casa', 'Uma aventura de pistas espalhadas pela casa.', 'Geral', 'Aventura', NULL, NULL, 'medio', 6, 12, 15, 15, 8,
     'Peça a um adulto para esconder pistas pela casa, cada uma levando à próxima, até chegar a um "tesouro" (pode ser um docinho ou um bilhete com um versículo). Sugestão de pistas temáticas: "Procure onde guardamos os pães, como na multiplicação que Jesus fez" (cozinha), "Procure onde dormimos em paz, como o menino Samuel no templo" (quarto). Use a criatividade da sua família!',
     NULL, 'publicado', NOW()),

    -- Colorir
    ('colorir', 'kadosys', 'Noé e os Animais na Arca', 'Desenho para colorir da arca cheia de animais.', 'Velho Testamento', 'Obediência', 'Gênesis', 'Noé', 'facil', 3, 8, NULL, 10, 5,
     NULL, NULL, 'publicado', NOW()),
    ('colorir', 'kadosys', 'Jesus, o Bom Pastor', 'Desenho para colorir de Jesus cuidando das ovelhinhas.', 'Novo Testamento', 'Cuidado de Deus', 'João', 'Jesus', 'facil', 3, 8, NULL, 10, 5,
     NULL, NULL, 'publicado', NOW()),
    ('colorir', 'kadosys', 'A Estrela de Belém', 'Desenho para colorir da estrela que guiou os sábios até Jesus.', 'Novo Testamento', 'Esperança', 'Mateus', NULL, 'facil', 3, 8, NULL, 10, 5,
     NULL, NULL, 'publicado', NOW()),
    ('colorir', 'kadosys', 'A Arca da Aliança', 'Desenho para colorir da arca que representava a presença de Deus.', 'Velho Testamento', 'Presença de Deus', 'Êxodo', NULL, 'medio', 5, 10, NULL, 10, 5,
     NULL, NULL, 'publicado', NOW()),

    -- Versiculo ilustrado
    ('versiculo_ilustrado', 'kadosys', 'Provérbios 3:5-6', 'Um versículo para confiar em Deus de todo o coração.', 'Geral', 'Confiança', 'Provérbios', NULL, 'facil', 5, 12, 2, 10, 5,
     '"Confie no Senhor de todo o seu coração e não se apoie em seu próprio entendimento." Provérbios 3:5',
     NULL, 'publicado', NOW()),
    ('versiculo_ilustrado', 'kadosys', 'Josué 1:9', 'Um versículo de coragem para os dias de medo.', 'Geral', 'Coragem', 'Josué', NULL, 'facil', 5, 12, 2, 10, 5,
     '"Seja forte e corajoso! Não se apavore, nem se desanime, pois o Senhor, o seu Deus, estará com você por onde você andar." Josué 1:9',
     NULL, 'publicado', NOW()),
    ('versiculo_ilustrado', 'kadosys', 'Romanos 8:28', 'Um versículo que lembra que Deus cuida de tudo.', 'Novo Testamento', 'Confiança', 'Romanos', NULL, 'medio', 7, 12, 2, 10, 5,
     '"Sabemos que Deus age em todas as coisas para o bem daqueles que o amam." Romanos 8:28',
     NULL, 'publicado', NOW()),
    ('versiculo_ilustrado', 'kadosys', 'Mateus 5:16', 'Um versículo sobre deixar a luz de Jesus brilhar.', 'Novo Testamento', 'Testemunho', 'Mateus', NULL, 'medio', 6, 12, 2, 10, 5,
     '"Assim brilhe a luz de vocês diante dos homens, para que vejam as boas obras de vocês e glorifiquem ao Pai que está nos céus." Mateus 5:16',
     NULL, 'publicado', NOW()),
    ('versiculo_ilustrado', 'kadosys', '1 João 4:19', 'Um versículo curtinho sobre o amor de Deus.', 'Novo Testamento', 'Amor de Deus', '1 João', NULL, 'facil', 4, 10, 2, 10, 5,
     '"Nós amamos porque ele nos amou primeiro." 1 João 4:19',
     NULL, 'publicado', NOW()),
    ('versiculo_ilustrado', 'kadosys', 'Gênesis 1:1', 'O primeiro versículo de toda a Bíblia.', 'Velho Testamento', 'Criação', 'Gênesis', NULL, 'facil', 3, 9, 2, 10, 5,
     '"No princípio, Deus criou os céus e a terra." Gênesis 1:1',
     NULL, 'publicado', NOW()),

    -- Devocional
    ('devocional', 'kadosys', 'Gratidão todos os dias', 'Um devocional sobre agradecer a Deus pelas coisas boas do dia.', 'Valores', 'Gratidão', NULL, NULL, 'facil', 4, 10, 4, 10, 5,
     'Hoje, tente pensar em três coisas boas que aconteceram no seu dia: pode ser uma brincadeira gostosa, uma comida gostosa ou um abraço de alguém que você ama. Agradeça a Deus por cada uma delas! A Bíblia diz para darmos graças em tudo, porque até nas coisas pequenas Deus está cuidando de nós.',
     NULL, 'publicado', NOW()),
    ('devocional', 'kadosys', 'Amar ao próximo como a mim mesmo', 'Um devocional sobre o segundo maior mandamento de Jesus.', 'Valores', 'Amor', NULL, 'Jesus', 'facil', 5, 11, 5, 10, 5,
     'Jesus disse que o segundo maior mandamento é amar ao próximo como a si mesmo. Isso quer dizer tratar os outros com o mesmo carinho que você gostaria de receber: dividir seus brinquedos, ajudar um amigo triste, ser gentil até com quem é diferente de você. Hoje, procure fazer algo gentil por alguém da sua casa ou da sua escola.',
     NULL, 'publicado', NOW()),
    ('devocional', 'kadosys', 'Perdoar de coração', 'Um devocional sobre a importância de perdoar como Deus perdoa.', 'Valores', 'Perdão', NULL, NULL, 'facil', 5, 11, 5, 10, 5,
     'Às vezes um amigo ou um irmão faz algo que nos machuca, e fica difícil perdoar. Jesus nos ensinou a perdoar sempre, porque Deus perdoa a gente todos os dias. Perdoar não quer dizer que o que a pessoa fez estava certo, mas que você decide não guardar raiva no coração. Pense em alguém que você precisa perdoar hoje e converse com Deus sobre isso.',
     NULL, 'publicado', NOW()),
    ('devocional', 'kadosys', 'Coragem para o dia a dia', 'Um devocional para os momentos de medo.', 'Valores', 'Coragem', 'Josué', NULL, 'facil', 5, 12, 5, 10, 5,
     'Todo mundo sente medo às vezes: medo do escuro, de uma prova, de fazer um novo amigo. Deus disse a Josué: "Seja forte e corajoso, pois eu estarei com você por onde você andar". Essa promessa também é para você! Quando sentir medo, lembre-se de que Deus está bem pertinho, cuidando de cada passo seu.',
     NULL, 'publicado', NOW()),

    -- Estudo
    ('estudo', 'kadosys', 'Os 10 Mandamentos explicados', 'Um estudo simples sobre as regras que Deus deu para o povo viver bem.', 'Velho Testamento', 'Obediência', 'Êxodo', 'Moisés', 'medio', 7, 12, 10, 15, 8,
     'Deus deu a Moisés dez regras importantes para o povo viver bem e em paz: amar somente a Deus, não fazer ídolos, respeitar o nome de Deus, descansar um dia por semana, honrar pai e mãe, não matar, ser fiel na família, não roubar, não mentir e não cobiçar o que é dos outros. Mais do que regras para seguir com medo, os 10 Mandamentos são um jeito de mostrar amor a Deus e às pessoas ao nosso redor.',
     NULL, 'publicado', NOW()),
    ('estudo', 'kadosys', 'O Fruto do Espírito', 'Um estudo sobre as características que crescem em quem segue a Deus.', 'Valores', 'Fruto do Espírito', 'Gálatas', NULL, 'medio', 7, 12, 10, 15, 8,
     'Quando o Espírito Santo vive em nós, Ele vai fazendo crescer certas características, como frutos em uma árvore: amor, alegria, paz, paciência, amabilidade, bondade, fidelidade, mansidão e domínio próprio. Ninguém nasce com esses frutos prontos - eles vão crescendo aos poucos, conforme nos aproximamos de Deus todos os dias, como uma árvore que precisa de água e sol para dar frutos bons.',
     NULL, 'publicado', NOW()),
    ('estudo', 'kadosys', 'Como ser um bom amigo', 'Um estudo sobre amizade, inspirado em Davi e Jônatas.', 'Valores', 'Amizade', '1 Samuel', 'Davi', 'facil', 6, 11, 8, 15, 8,
     'A Bíblia fala sobre a amizade de Davi e Jônatas, que se amavam como irmãos e cuidavam um do outro mesmo em momentos difíceis. Provérbios diz que um amigo ama em todo o tempo. Ser um bom amigo é ouvir quando o outro está triste, comemorar quando ele está feliz, falar a verdade com carinho e perdoar quando ele erra. Quem você pode ser um bom amigo hoje?',
     NULL, 'publicado', NOW()),

    -- Atividade
    ('atividade', 'kadosys', 'Complete o Versículo', 'Preencha a palavra que falta em cada versículo.', 'Geral', 'Memorização', NULL, NULL, 'medio', 6, 11, 8, 15, 8,
     'Complete os versículos com a palavra que falta (as respostas estão no fim):
1. "O Senhor é o meu ___, nada me faltará." (Salmos 23:1)
2. "Tudo posso naquele que me ___." (Filipenses 4:13)
3. "Tudo o que fizerem, façam de ___, como para o Senhor." (Colossenses 3:23)
4. "Confie no Senhor de todo o ___." (Provérbios 3:5)
Respostas: 1) pastor  2) fortalece  3) coração  4) coração',
     NULL, 'publicado', NOW()),
    ('atividade', 'kadosys', 'Ligue o Personagem à sua História', 'Uma atividade para relembrar quem fez o quê na Bíblia.', 'Geral', 'Personagens', NULL, NULL, 'facil', 5, 10, 8, 15, 8,
     'Tente ligar cada personagem à sua história, sem olhar a resposta:
Noé - construiu uma arca
Davi - enfrentou um gigante
Jonas - foi engolido por um peixe
Daniel - ficou na cova dos leões
Moisés - abriu o Mar Vermelho
Depois de tentar de cabeça, confira se acertou todas!',
     NULL, 'publicado', NOW()),
    ('atividade', 'kadosys', 'Desenhe a sua Oração', 'Uma atividade criativa para orar através de um desenho.', 'Valores', 'Oração', NULL, NULL, 'facil', 3, 9, 10, 15, 8,
     'Pegue papel e lápis de cor e desenhe algo pelo que você quer agradecer a Deus hoje, ou algo que você quer pedir a Ele em oração. Pode ser sua família, um brinquedo favorito, ou até um amigo que está doente. Depois, mostre o desenho para alguém e conte o que você desenhou - isso também é uma forma linda de orar!',
     NULL, 'publicado', NOW()),

    -- Desafio
    ('desafio', 'kadosys', 'Desafio da Bondade', 'Uma semana praticando bondade com quem está por perto.', 'Valores', 'Bondade', NULL, NULL, 'facil', 5, 12, NULL, 20, 10,
     'Durante essa semana, faça pelo menos uma coisa boa por dia por alguém: ajudar em casa sem que peçam, dividir um lanche, elogiar um colega, ou dar um abraço em quem está triste. No fim da semana, conte para seu professor ou responsável tudo o que você fez!',
     NULL, 'publicado', NOW()),
    ('desafio', 'kadosys', 'Desafio do Silêncio com Deus', 'Cinco minutinhos por dia só para agradecer a Deus.', 'Valores', 'Oração', NULL, NULL, 'medio', 6, 12, NULL, 20, 10,
     'Escolha um momento do seu dia para ficar 5 minutinhos em silêncio, só pensando em Deus e agradecendo por tudo que Ele fez por você, sem pedir nada. No começo pode parecer difícil ficar quietinho, mas com a prática fica mais fácil sentir a paz de Deus.',
     NULL, 'publicado', NOW()),
    ('desafio', 'kadosys', 'Desafio da Leitura Bíblica', 'Ler um livro pequeno da Bíblia, um capítulo por dia.', 'Geral', 'A Bíblia', NULL, NULL, 'medio', 7, 12, NULL, 20, 10,
     'Escolha um livro pequeno da Bíblia, como Rute ou Jonas, e tente ler um capítulo por dia até terminar. Peça ajuda a um adulto se encontrar palavras difíceis. No fim, conte para alguém da sua família a parte que você mais gostou da história!',
     NULL, 'publicado', NOW()),

    -- Plano de leitura
    ('plano_leitura', 'kadosys', '7 dias com Provérbios', 'Um versículo por dia para aprender a viver com sabedoria.', 'Geral', 'Sabedoria', 'Provérbios', NULL, 'medio', 7, 12, NULL, 20, 10,
     'Dia 1: Provérbios 1:7 - "O temor do Senhor é o princípio do conhecimento."
Dia 2: Provérbios 3:5 - "Confie no Senhor de todo o coração."
Dia 3: Provérbios 15:1 - "A resposta mansa desvia o furor."
Dia 4: Provérbios 17:17 - "Em todo o tempo ama o amigo."
Dia 5: Provérbios 18:10 - "O nome do Senhor é uma torre forte."
Dia 6: Provérbios 22:6 - "Ensina a criança no caminho em que deve andar."
Dia 7: Provérbios 31:30 - "A mulher que teme ao Senhor, essa será louvada."',
     NULL, 'publicado', NOW()),
    ('plano_leitura', 'kadosys', '5 dias sobre o Fruto do Espírito', 'Um versículo por dia para conhecer melhor cada fruto.', 'Valores', 'Fruto do Espírito', 'Gálatas', NULL, 'facil', 6, 11, NULL, 20, 10,
     'Dia 1: Amor - 1 Coríntios 13:4 - "O amor é paciente, o amor é bondoso."
Dia 2: Alegria - Neemias 8:10 - "A alegria do Senhor é a força de vocês."
Dia 3: Paz - João 14:27 - "Deixo-lhes a paz; a minha paz lhes dou."
Dia 4: Paciência - Tiago 1:4 - "Que a perseverança conclua a sua obra."
Dia 5: Domínio próprio - Provérbios 25:28 - "Como cidade derrubada, sem muro, é o homem que não sabe controlar-se."',
     NULL, 'publicado', NOW()),

    -- PDF
    ('pdf', 'kadosys', 'Cartilha: Livros da Bíblia', 'Material para imprimir com todos os livros da Bíblia para aprender.', 'Geral', 'A Bíblia', NULL, NULL, 'medio', 7, 12, NULL, 15, 8,
     'Adicione o arquivo PDF em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),
    ('pdf', 'kadosys', 'Diploma Kids KADOSYS', 'Diploma para entregar às crianças que completarem a biblioteca.', 'Geral', 'Conquistas', NULL, NULL, 'facil', 4, 12, NULL, 10, 5,
     'Adicione o arquivo PDF do diploma em Kids > Conteúdos > Editar, para entregar às crianças que completarem a biblioteca.', NULL, 'publicado', NOW()),

    -- HQ
    ('hq', 'kadosys', 'Daniel, o Homem Corajoso', 'Em quadrinhos: a coragem de Daniel diante do perigo.', 'Velho Testamento', 'Coragem', 'Daniel', 'Daniel', 'facil', 6, 11, 10, 15, 8,
     'Adicione o arquivo da HQ (PDF ou imagens) em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),
    ('hq', 'kadosys', 'A Vida de Jesus em Quadrinhos', 'Em quadrinhos: os principais momentos da vida de Jesus.', 'Novo Testamento', 'Jesus', 'Mateus', 'Jesus', 'medio', 6, 12, 12, 15, 8,
     'Adicione o arquivo da HQ (PDF ou imagens) em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),

    -- Slide
    ('slide', 'kadosys', 'Os 12 Discípulos de Jesus', 'Uma apresentação simples conhecendo cada um dos discípulos.', 'Novo Testamento', 'Discípulos', NULL, 'Jesus', 'medio', 7, 12, 8, 15, 8,
     'Adicione o arquivo da apresentação em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),
    ('slide', 'kadosys', 'Mapa da Terra Santa para Crianças', 'Uma apresentação mostrando os lugares mais importantes da Bíblia.', 'Geral', 'Geografia Bíblica', NULL, NULL, 'medio', 7, 12, 8, 15, 8,
     'Adicione o arquivo da apresentação em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),

    -- Video
    ('video', 'kadosys', 'Davi Tocando Harpa para o Rei Saul', 'A música de Davi trazia paz para o coração do rei.', 'Velho Testamento', 'Louvor', '1 Samuel', 'Davi', 'facil', 5, 10, 5, 15, 8,
     'Adicione o link do vídeo em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),
    ('video', 'kadosys', 'A Criação em 7 Dias', 'Um vídeo animado mostrando a criação do mundo por Deus.', 'Velho Testamento', 'Criação', 'Gênesis', NULL, 'facil', 4, 9, 6, 15, 8,
     'Adicione o link do vídeo em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),

    -- Audio
    ('audio', 'kadosys', 'Louvor Infantil: Deus é Bom', 'Uma música alegre para cantar e louvar a Deus.', 'Louvor', 'Alegria', NULL, NULL, 'facil', 3, 9, 3, 10, 5,
     'Adicione o arquivo de áudio em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),
    ('audio', 'kadosys', 'Salmo 100 narrado para crianças', 'Uma narração alegre do Salmo de gratidão.', 'Louvor', 'Gratidão', 'Salmos', NULL, 'facil', 3, 9, 4, 10, 5,
     'Adicione o arquivo de áudio em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW());
