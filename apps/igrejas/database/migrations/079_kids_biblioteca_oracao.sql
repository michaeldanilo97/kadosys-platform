-- Ajuste 187: Biblioteca de Oracao - novo tipo de conteudo Kids ('oracao')
-- com oracoes prontas, curtas e simples pro dia a dia da crianca (comecar
-- o dia, antes de comer, antes de dormir, gratidao, familia, escola,
-- medo, perdao). Reaproveita 100% da infraestrutura generica ja existente
-- (listagem por tipo, leitura em voz alta, XP/moedas, tela de conteudo) -
-- ganha o gate de reacao emocional ("Como isso te fez sentir?") junto
-- com historia/devocional/versiculo_ilustrado (ver
-- Igrejas\Models\KidsConteudo::TIPOS e resources/views/kids/show.php).

ALTER TABLE kids_conteudos
    MODIFY tipo ENUM(
        'historia', 'video', 'audio', 'slide', 'hq', 'devocional', 'estudo',
        'plano_leitura', 'quiz', 'colorir', 'pdf', 'atividade', 'jogo',
        'desafio', 'versiculo_ilustrado', 'oracao'
    ) NOT NULL;

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('oracao', 'kadosys', 'Oração da Manhã', 'Uma oração curtinha pra começar o dia com Deus.', 'Oração', 'Começo do Dia', NULL, NULL, 'facil', 3, 10, 1, 10, 5,
     'Bom dia, Deus! Obrigado por mais um dia lindo pra viver. Cuida de mim e da minha família hoje, e me ajuda a ser gentil e feliz com todo mundo. Em nome de Jesus, amém.',
     NULL, 'publicado', NOW()),
    ('oracao', 'kadosys', 'Oração Antes de Dormir', 'Uma oração pra agradecer pelo dia e pedir um sono tranquilo.', 'Oração', 'Antes de Dormir', NULL, NULL, 'facil', 3, 10, 1, 10, 5,
     'Deus, obrigado por esse dia. Se eu errei em alguma coisa, me perdoa. Cuida de mim enquanto eu durmo e de toda a minha família também. Amanhã eu quero acordar mais feliz e mais perto de Ti. Em nome de Jesus, amém.',
     NULL, 'publicado', NOW()),
    ('oracao', 'kadosys', 'Oração Antes de Comer', 'Uma oração de agradecimento pela comida na mesa.', 'Oração', 'Antes de Comer', NULL, NULL, 'facil', 3, 10, 1, 10, 5,
     'Obrigado, Deus, por essa comida gostosa! Obrigado por cuidar de mim todos os dias. Abençoa essa refeição e as mãos que a prepararam. Em nome de Jesus, amém.',
     NULL, 'publicado', NOW()),
    ('oracao', 'kadosys', 'Oração de Gratidão', 'Uma oração só pra agradecer, sem pedir nada.', 'Oração', 'Gratidão', NULL, NULL, 'facil', 3, 10, 1, 10, 5,
     'Deus, hoje eu quero só agradecer! Obrigado pela minha família, pelos meus amigos, pela minha casa e por Você estar sempre comigo. Você é muito bom pra mim. Em nome de Jesus, amém.',
     NULL, 'publicado', NOW()),
    ('oracao', 'kadosys', 'Oração pela Família', 'Uma oração pedindo a Deus que cuide de quem mora com você.', 'Oração', 'Família', NULL, NULL, 'facil', 3, 10, 1, 10, 5,
     'Deus, abençoa meu papai, minha mamãe e todos que moram comigo. Cuida da saúde deles, enche a nossa casa de paz e de amor. Nos ajuda a cuidar bem uns dos outros. Em nome de Jesus, amém.',
     NULL, 'publicado', NOW()),
    ('oracao', 'kadosys', 'Oração para Não Ter Medo', 'Uma oração de coragem, baseada em Josué 1:9, pra quando bater o medo.', 'Oração', 'Quando Tenho Medo', 'Josué', NULL, 'facil', 3, 10, 1, 10, 5,
     'Deus, às vezes eu sinto medo do escuro, de coisas novas ou de ficar sozinho. Mas a Bíblia diz que Você está sempre comigo e nunca vai me abandonar. Me ajuda a ser corajoso e confiar em Ti. Em nome de Jesus, amém.',
     NULL, 'publicado', NOW()),
    ('oracao', 'kadosys', 'Oração Antes da Escola', 'Uma oração pra pedir ajuda pra aprender e ser um bom amigo na escola.', 'Oração', 'Escola', NULL, NULL, 'facil', 3, 10, 1, 10, 5,
     'Deus, hoje eu vou pra escola. Me ajuda a aprender bem, a ser um bom amigo pros meus colegas e a ter paciência com as tarefas. Protege meu caminho de ida e de volta. Em nome de Jesus, amém.',
     NULL, 'publicado', NOW()),
    ('oracao', 'kadosys', 'Oração de Perdão', 'Uma oração pra quando erramos e queremos pedir perdão a Deus.', 'Oração', 'Perdão', NULL, NULL, 'facil', 3, 10, 1, 10, 5,
     'Deus, hoje eu fiz uma coisa errada e quero pedir perdão. Me ajuda a pedir desculpas pra quem eu machuquei e a ser uma pessoa melhor amanhã. Obrigado por sempre me perdoar quando eu erro. Em nome de Jesus, amém.',
     NULL, 'publicado', NOW());
