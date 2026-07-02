-- KADOSYS Igrejas - Migracao 005
-- Tabelas do modulo Projecao/Telao:
--   - configuracoes_igreja: dados gerais da igreja (por enquanto, so a logo
--     usada no fadeout do video).
--   - biblia_livros / biblia_versiculos: texto biblico usado na projecao.
--     Os 66 livros (dados de referencia, nao protegidos por direito
--     autoral) ja sao inseridos por esta migracao. Os versiculos ficam
--     vazios ate a importacao do texto (ver database/seed_biblia.php).
--   - projecao_sessoes / projecao_estados: sessao de projecao de um culto,
--     com acesso por token (telao) e PIN (tablet do preletor), e o estado
--     atual exibido (versiculo biblico, video do YouTube ou logo),
--     sincronizado entre as telas por polling.

CREATE TABLE IF NOT EXISTS configuracoes_igreja (
    id TINYINT UNSIGNED PRIMARY KEY,
    nome_igreja VARCHAR(150) NULL,
    logo_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuracoes_igreja (id, nome_igreja, logo_path) VALUES (1, NULL, NULL);


CREATE TABLE IF NOT EXISTS biblia_livros (
    id TINYINT UNSIGNED PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    abreviacao VARCHAR(10) NOT NULL,
    testamento ENUM('antigo', 'novo') NOT NULL,
    total_capitulos SMALLINT UNSIGNED NOT NULL,
    ordem SMALLINT UNSIGNED NOT NULL,
    UNIQUE KEY biblia_livros_ordem_unique (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS biblia_versiculos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    livro_id TINYINT UNSIGNED NOT NULL,
    capitulo SMALLINT UNSIGNED NOT NULL,
    versiculo SMALLINT UNSIGNED NOT NULL,
    texto TEXT NOT NULL,
    UNIQUE KEY biblia_versiculos_unique (livro_id, capitulo, versiculo),
    KEY biblia_versiculos_capitulo_index (livro_id, capitulo),
    CONSTRAINT biblia_versiculos_livro_id_foreign
        FOREIGN KEY (livro_id) REFERENCES biblia_livros (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO biblia_livros (id, nome, abreviacao, testamento, total_capitulos, ordem) VALUES
(1, 'Gênesis', 'Gn', 'antigo', 50, 1),
(2, 'Êxodo', 'Ex', 'antigo', 40, 2),
(3, 'Levítico', 'Lv', 'antigo', 27, 3),
(4, 'Números', 'Nm', 'antigo', 36, 4),
(5, 'Deuteronômio', 'Dt', 'antigo', 34, 5),
(6, 'Josué', 'Js', 'antigo', 24, 6),
(7, 'Juízes', 'Jz', 'antigo', 21, 7),
(8, 'Rute', 'Rt', 'antigo', 4, 8),
(9, '1 Samuel', '1Sm', 'antigo', 31, 9),
(10, '2 Samuel', '2Sm', 'antigo', 24, 10),
(11, '1 Reis', '1Rs', 'antigo', 22, 11),
(12, '2 Reis', '2Rs', 'antigo', 25, 12),
(13, '1 Crônicas', '1Cr', 'antigo', 29, 13),
(14, '2 Crônicas', '2Cr', 'antigo', 36, 14),
(15, 'Esdras', 'Ed', 'antigo', 10, 15),
(16, 'Neemias', 'Ne', 'antigo', 13, 16),
(17, 'Ester', 'Et', 'antigo', 10, 17),
(18, 'Jó', 'Jó', 'antigo', 42, 18),
(19, 'Salmos', 'Sl', 'antigo', 150, 19),
(20, 'Provérbios', 'Pv', 'antigo', 31, 20),
(21, 'Eclesiastes', 'Ec', 'antigo', 12, 21),
(22, 'Cânticos', 'Ct', 'antigo', 8, 22),
(23, 'Isaías', 'Is', 'antigo', 66, 23),
(24, 'Jeremias', 'Jr', 'antigo', 52, 24),
(25, 'Lamentações', 'Lm', 'antigo', 5, 25),
(26, 'Ezequiel', 'Ez', 'antigo', 48, 26),
(27, 'Daniel', 'Dn', 'antigo', 12, 27),
(28, 'Oséias', 'Os', 'antigo', 14, 28),
(29, 'Joel', 'Jl', 'antigo', 3, 29),
(30, 'Amós', 'Am', 'antigo', 9, 30),
(31, 'Obadias', 'Ob', 'antigo', 1, 31),
(32, 'Jonas', 'Jn', 'antigo', 4, 32),
(33, 'Miquéias', 'Mq', 'antigo', 7, 33),
(34, 'Naum', 'Na', 'antigo', 3, 34),
(35, 'Habacuque', 'Hc', 'antigo', 3, 35),
(36, 'Sofonias', 'Sf', 'antigo', 3, 36),
(37, 'Ageu', 'Ag', 'antigo', 2, 37),
(38, 'Zacarias', 'Zc', 'antigo', 14, 38),
(39, 'Malaquias', 'Ml', 'antigo', 4, 39),
(40, 'Mateus', 'Mt', 'novo', 28, 40),
(41, 'Marcos', 'Mc', 'novo', 16, 41),
(42, 'Lucas', 'Lc', 'novo', 24, 42),
(43, 'João', 'Jo', 'novo', 21, 43),
(44, 'Atos', 'At', 'novo', 28, 44),
(45, 'Romanos', 'Rm', 'novo', 16, 45),
(46, '1 Coríntios', '1Co', 'novo', 16, 46),
(47, '2 Coríntios', '2Co', 'novo', 13, 47),
(48, 'Gálatas', 'Gl', 'novo', 6, 48),
(49, 'Efésios', 'Ef', 'novo', 6, 49),
(50, 'Filipenses', 'Fp', 'novo', 4, 50),
(51, 'Colossenses', 'Cl', 'novo', 4, 51),
(52, '1 Tessalonicenses', '1Ts', 'novo', 5, 52),
(53, '2 Tessalonicenses', '2Ts', 'novo', 3, 53),
(54, '1 Timóteo', '1Tm', 'novo', 6, 54),
(55, '2 Timóteo', '2Tm', 'novo', 4, 55),
(56, 'Tito', 'Tt', 'novo', 3, 56),
(57, 'Filemom', 'Fm', 'novo', 1, 57),
(58, 'Hebreus', 'Hb', 'novo', 13, 58),
(59, 'Tiago', 'Tg', 'novo', 5, 59),
(60, '1 Pedro', '1Pe', 'novo', 5, 60),
(61, '2 Pedro', '2Pe', 'novo', 3, 61),
(62, '1 João', '1Jo', 'novo', 5, 62),
(63, '2 João', '2Jo', 'novo', 1, 63),
(64, '3 João', '3Jo', 'novo', 1, 64),
(65, 'Judas', 'Jd', 'novo', 1, 65),
(66, 'Apocalipse', 'Ap', 'novo', 22, 66)
ON DUPLICATE KEY UPDATE nome = VALUES(nome);


CREATE TABLE IF NOT EXISTS projecao_sessoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token CHAR(40) NOT NULL,
    pin CHAR(6) NOT NULL,
    criado_por INT UNSIGNED NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY projecao_sessoes_token_unique (token),
    KEY projecao_sessoes_pin_index (pin),
    KEY projecao_sessoes_ativo_index (ativo),
    CONSTRAINT projecao_sessoes_criado_por_foreign
        FOREIGN KEY (criado_por) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projecao_estados (
    sessao_id INT UNSIGNED PRIMARY KEY,
    modo ENUM('biblia', 'video', 'logo', 'blank') NOT NULL DEFAULT 'blank',
    livro_id TINYINT UNSIGNED NULL,
    capitulo SMALLINT UNSIGNED NULL,
    versiculo_inicio SMALLINT UNSIGNED NULL,
    versiculo_fim SMALLINT UNSIGNED NULL,
    video_url VARCHAR(255) NULL,
    video_estado ENUM('parado', 'tocando', 'pausado', 'fadeout') NOT NULL DEFAULT 'parado',
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT projecao_estados_sessao_id_foreign
        FOREIGN KEY (sessao_id) REFERENCES projecao_sessoes (id) ON DELETE CASCADE,
    CONSTRAINT projecao_estados_livro_id_foreign
        FOREIGN KEY (livro_id) REFERENCES biblia_livros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
