-- KADOSYS Igrejas - Instalacao completa do banco de dados
-- ============================================================================
-- Este arquivo reflete o schema completo mais recente (equivalente a
-- rodar 001 a 006 em sequencia). Ele existe apenas para facilitar a
-- INSTALACAO NOVA (rodar um unico arquivo em vez de varios).
--
-- As migracoes numeradas em database/migrations/ continuam sendo a fonte
-- de verdade e o historico incremental do banco: sempre que um novo modulo
-- for implementado (ou o schema evolui, como em 006), uma nova migracao
-- numerada e criada la, e este arquivo e atualizado para refletir o
-- schema completo mais recente.
--
-- Uso:
--   a) Instalacao nova, direto por este arquivo unico:
--        mysql -u usuario -p nome_do_banco < database/install.sql
--   b) Banco ja instalado ate a migracao 005: rode so a diferenca:
--        mysql -u usuario -p nome_do_banco < database/migrations/006_add_biblia_versoes.sql
--   c) Instalacao nova, migracoes numeradas uma a uma, em ordem:
--        mysql -u usuario -p nome_do_banco < database/migrations/001_create_tables.sql
--        mysql -u usuario -p nome_do_banco < database/migrations/002_create_membros_table.sql
--        mysql -u usuario -p nome_do_banco < database/migrations/003_create_ministerios_tables.sql
--        mysql -u usuario -p nome_do_banco < database/migrations/004_create_cultos_tables.sql
--        mysql -u usuario -p nome_do_banco < database/migrations/005_create_projecao_tables.sql
--        mysql -u usuario -p nome_do_banco < database/migrations/006_add_biblia_versoes.sql
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 001 - Autenticacao (users, remember_tokens, password_resets)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'usuario') NOT NULL DEFAULT 'admin',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY remember_tokens_hash_unique (token_hash),
    KEY remember_tokens_user_id_index (user_id),
    CONSTRAINT remember_tokens_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY password_resets_email_index (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- O usuario administrador inicial NAO e criado por este script SQL para
-- evitar inserir um hash de senha invalido/nao verificavel diretamente em
-- SQL estatico. Use o script PHP database/seed_admin.php apos rodar esta
-- instalacao, que gera o hash com password_hash() (bcrypt) corretamente:
--
--   php database/seed_admin.php "Administrador" "[email protected]" "sua-senha-forte"


-- ----------------------------------------------------------------------------
-- 002 - Modulo Membros
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS membros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    telefone VARCHAR(30) NULL,
    data_nascimento DATE NULL,
    genero ENUM('feminino', 'masculino', 'outro') NULL,
    estado_civil ENUM('solteiro', 'casado', 'divorciado', 'viuvo', 'outro') NULL,
    endereco VARCHAR(255) NULL,
    cep VARCHAR(9) NULL,
    cidade VARCHAR(100) NULL,
    estado CHAR(2) NULL,
    data_membresia DATE NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY membros_nome_index (nome),
    KEY membros_status_index (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 003 - Modulo Ministerios
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS ministerios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    lider_membro_id INT UNSIGNED NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ministerios_nome_index (nome),
    KEY ministerios_status_index (status),
    CONSTRAINT ministerios_lider_membro_id_foreign
        FOREIGN KEY (lider_membro_id) REFERENCES membros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ministerio_membros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ministerio_id INT UNSIGNED NOT NULL,
    membro_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ministerio_membros_unique (ministerio_id, membro_id),
    CONSTRAINT ministerio_membros_ministerio_id_foreign
        FOREIGN KEY (ministerio_id) REFERENCES ministerios (id) ON DELETE CASCADE,
    CONSTRAINT ministerio_membros_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 004 - Modulo Cultos
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS cultos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    data DATE NOT NULL,
    hora TIME NULL,
    local VARCHAR(150) NULL,
    descricao TEXT NULL,
    status ENUM('agendado', 'realizado', 'cancelado') NOT NULL DEFAULT 'agendado',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY cultos_data_index (data),
    KEY cultos_status_index (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS culto_frequencias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    culto_id INT UNSIGNED NOT NULL,
    membro_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY culto_frequencias_unique (culto_id, membro_id),
    CONSTRAINT culto_frequencias_culto_id_foreign
        FOREIGN KEY (culto_id) REFERENCES cultos (id) ON DELETE CASCADE,
    CONSTRAINT culto_frequencias_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 005 - Modulo Projecao/Telao
-- ----------------------------------------------------------------------------

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
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL DEFAULT 'essencial',
    cep VARCHAR(9) NULL,
    endereco VARCHAR(190) NULL,
    numero VARCHAR(20) NULL,
    cidade VARCHAR(100) NULL,
    estado CHAR(2) NULL,
    cadastro_membros_habilitado TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuracoes_igreja (id, nome_igreja, logo_path, plano) VALUES (1, NULL, NULL, 'essencial');


CREATE TABLE IF NOT EXISTS biblia_livros (
    id TINYINT UNSIGNED PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    abreviacao VARCHAR(10) NOT NULL,
    testamento ENUM('antigo', 'novo') NOT NULL,
    total_capitulos SMALLINT UNSIGNED NOT NULL,
    ordem SMALLINT UNSIGNED NOT NULL,
    UNIQUE KEY biblia_livros_ordem_unique (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coluna "versao" e chave unica ja refletem a migracao 006 (instalacao
-- nova fica pronta em um unico passo; quem ja tinha so a 005 aplicada
-- roda a 006 separadamente).
CREATE TABLE IF NOT EXISTS biblia_versiculos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    livro_id TINYINT UNSIGNED NOT NULL,
    versao VARCHAR(10) NOT NULL DEFAULT 'nvi',
    capitulo SMALLINT UNSIGNED NOT NULL,
    versiculo SMALLINT UNSIGNED NOT NULL,
    texto TEXT NOT NULL,
    KEY biblia_versiculos_livro_id_index (livro_id),
    UNIQUE KEY biblia_versiculos_unique (versao, livro_id, capitulo, versiculo),
    KEY biblia_versiculos_capitulo_index (versao, livro_id, capitulo),
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
    biblia_versao VARCHAR(10) NULL DEFAULT 'nvi',
    biblia_marcacao TEXT NULL,
    capitulo SMALLINT UNSIGNED NULL,
    versiculo_inicio SMALLINT UNSIGNED NULL,
    versiculo_fim SMALLINT UNSIGNED NULL,
    video_url VARCHAR(255) NULL,
    video_estado ENUM('parado', 'tocando', 'pausado', 'fadeout') NOT NULL DEFAULT 'parado',
    video_tempo_atual SMALLINT UNSIGNED NULL,
    video_duracao SMALLINT UNSIGNED NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    leitura_id INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT projecao_estados_sessao_id_foreign
        FOREIGN KEY (sessao_id) REFERENCES projecao_sessoes (id) ON DELETE CASCADE,
    CONSTRAINT projecao_estados_livro_id_foreign
        FOREIGN KEY (livro_id) REFERENCES biblia_livros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 010 - Assinaturas recorrentes via Mercado Pago (ver Igrejas\Models\Plano)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS assinaturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL,
    mp_preapproval_id VARCHAR(64) NULL,
    mp_payer_email VARCHAR(190) NULL,
    status ENUM('pendente', 'autorizada', 'pausada', 'cancelada') NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mp_preapproval_id (mp_preapproval_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assinatura_eventos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assinatura_id INT UNSIGNED NULL,
    tipo VARCHAR(40) NOT NULL,
    payload TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assinatura_eventos_assinatura
        FOREIGN KEY (assinatura_id) REFERENCES assinaturas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 015 - Modulo Financeiro
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS financeiro_categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    status ENUM('ativa', 'inativa') NOT NULL DEFAULT 'ativa',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY financeiro_categorias_tipo_index (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financeiro_lancamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('entrada', 'saida') NOT NULL,
    categoria_id INT UNSIGNED NULL,
    membro_id INT UNSIGNED NULL,
    descricao VARCHAR(190) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('dinheiro', 'pix', 'cartao', 'transferencia', 'boleto', 'outro') NOT NULL DEFAULT 'dinheiro',
    data_lancamento DATE NOT NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY financeiro_lancamentos_tipo_index (tipo),
    KEY financeiro_lancamentos_data_index (data_lancamento),
    CONSTRAINT financeiro_lancamentos_categoria_id_foreign
        FOREIGN KEY (categoria_id) REFERENCES financeiro_categorias (id) ON DELETE SET NULL,
    CONSTRAINT financeiro_lancamentos_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO financeiro_categorias (nome, tipo) VALUES
    ('Dizimo', 'entrada'),
    ('Oferta', 'entrada'),
    ('Oferta especial / Missoes', 'entrada'),
    ('Doacao', 'entrada'),
    ('Aluguel e contas do templo', 'saida'),
    ('Manutencao', 'saida'),
    ('Materiais e suprimentos', 'saida'),
    ('Salarios e honorarios', 'saida'),
    ('Eventos', 'saida'),
    ('Missoes e acoes sociais', 'saida'),
    ('Outros', 'saida');


-- ----------------------------------------------------------------------------
-- 016 - Modulo Grupos
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS grupos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo ENUM('celula', 'classe', 'grupo') NOT NULL DEFAULT 'grupo',
    descricao TEXT NULL,
    lider_membro_id INT UNSIGNED NULL,
    dia_semana ENUM('domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado') NULL,
    horario TIME NULL,
    local VARCHAR(150) NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY grupos_nome_index (nome),
    KEY grupos_status_index (status),
    CONSTRAINT grupos_lider_membro_id_foreign
        FOREIGN KEY (lider_membro_id) REFERENCES membros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grupo_membros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT UNSIGNED NOT NULL,
    membro_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY grupo_membros_unique (grupo_id, membro_id),
    CONSTRAINT grupo_membros_grupo_id_foreign
        FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE CASCADE,
    CONSTRAINT grupo_membros_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 017 - Modulo Agenda
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS agenda_eventos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    tipo ENUM('evento', 'reuniao', 'reserva', 'outro') NOT NULL DEFAULT 'evento',
    data DATE NOT NULL,
    hora_inicio TIME NULL,
    hora_fim TIME NULL,
    local VARCHAR(150) NULL,
    responsavel_membro_id INT UNSIGNED NULL,
    descricao TEXT NULL,
    status ENUM('agendado', 'realizado', 'cancelado') NOT NULL DEFAULT 'agendado',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY agenda_eventos_data_index (data),
    KEY agenda_eventos_status_index (status),
    CONSTRAINT agenda_eventos_responsavel_membro_id_foreign
        FOREIGN KEY (responsavel_membro_id) REFERENCES membros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 018 - Modulo Usuarios e Permissoes (a coluna "role" de "users" ja
-- nasce como ENUM, ver secao 001 no topo deste arquivo)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS user_modulos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    modulo_slug VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_modulos_unique (user_id, modulo_slug),
    CONSTRAINT user_modulos_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 019 - Modulo Patrimonio
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS patrimonio_bens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    categoria ENUM('imovel', 'veiculo', 'equipamento', 'mobiliario', 'eletronico', 'outro') NOT NULL DEFAULT 'outro',
    numero_patrimonio VARCHAR(50) NULL,
    descricao TEXT NULL,
    valor_estimado DECIMAL(10,2) NULL,
    data_aquisicao DATE NULL,
    local VARCHAR(150) NULL,
    status ENUM('ativo', 'manutencao', 'baixado') NOT NULL DEFAULT 'ativo',
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY patrimonio_bens_categoria_index (categoria),
    KEY patrimonio_bens_status_index (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 020 - Modulo Comunicacao
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS comunicacao_avisos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    conteudo TEXT NOT NULL,
    publico_alvo ENUM('todos', 'lideranca') NOT NULL DEFAULT 'todos',
    status ENUM('rascunho', 'publicado', 'arquivado') NOT NULL DEFAULT 'rascunho',
    data_publicacao DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY comunicacao_avisos_status_index (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 022 - Leitura de avisos por usuario (barra lateral do painel)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS comunicacao_aviso_leituras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aviso_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    lido_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY comunicacao_aviso_leituras_unique (aviso_id, user_id),
    CONSTRAINT comunicacao_aviso_leituras_aviso_id_foreign
        FOREIGN KEY (aviso_id) REFERENCES comunicacao_avisos (id) ON DELETE CASCADE,
    CONSTRAINT comunicacao_aviso_leituras_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
