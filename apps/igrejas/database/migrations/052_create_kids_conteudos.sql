-- KADOSYS Igrejas - Migracao 052
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids (Fase 2 - biblioteca de conteudo): historias,
-- videos, audios, devocionais, quiz, atividades para colorir etc.
-- "origem" distingue o conteudo oficial da KADOSYS (somente leitura
-- pra igreja, inserido via seed/install.sql) do conteudo proprio de
-- cada igreja (criado pela propria equipe em Kids > Conteudos).
-- quiz_perguntas guarda um JSON simples (pergunta/alternativas/
-- resposta) como TEXT, sem depender do tipo nativo JSON do banco -
-- mais compativel entre versoes de MySQL/MariaDB de hospedagem
-- compartilhada.

CREATE TABLE IF NOT EXISTS kids_conteudos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM(
        'historia', 'video', 'audio', 'slide', 'hq', 'devocional', 'estudo',
        'plano_leitura', 'quiz', 'colorir', 'pdf', 'atividade', 'jogo',
        'desafio', 'versiculo_ilustrado'
    ) NOT NULL,
    origem ENUM('kadosys', 'igreja') NOT NULL DEFAULT 'igreja',
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    categoria VARCHAR(60) NULL,
    tema VARCHAR(100) NULL,
    livro_biblico VARCHAR(60) NULL,
    personagem VARCHAR(100) NULL,
    dificuldade ENUM('facil', 'medio', 'dificil') NULL,
    idade_min TINYINT UNSIGNED NULL,
    idade_max TINYINT UNSIGNED NULL,
    duracao_minutos SMALLINT UNSIGNED NULL,
    xp_recompensa SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    moedas_recompensa SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    texto_conteudo LONGTEXT NULL,
    midia_url VARCHAR(255) NULL,
    midia_path VARCHAR(255) NULL,
    capa_path VARCHAR(255) NULL,
    quiz_perguntas TEXT NULL,
    status ENUM('rascunho', 'publicado') NOT NULL DEFAULT 'publicado',
    criado_por INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY kids_conteudos_tipo_index (tipo),
    KEY kids_conteudos_status_index (status),
    KEY kids_conteudos_origem_index (origem),
    KEY kids_conteudos_categoria_index (categoria),
    CONSTRAINT kids_conteudos_criado_por_foreign
        FOREIGN KEY (criado_por) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
