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
    musico TINYINT(1) NOT NULL DEFAULT 0,
    lider_louvor TINYINT(1) NOT NULL DEFAULT 0,
    cargo ENUM('musico', 'midia', 'equipamento', 'membro') NOT NULL DEFAULT 'membro',
    instrumento VARCHAR(20) NULL,
    foto_path VARCHAR(255) NULL,
    membro_id INT UNSIGNED NULL,
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
    cpf VARCHAR(14) NULL,
    rg VARCHAR(20) NULL,
    naturalidade VARCHAR(100) NULL,
    endereco VARCHAR(255) NULL,
    cep VARCHAR(9) NULL,
    logradouro VARCHAR(150) NULL,
    numero VARCHAR(20) NULL,
    complemento VARCHAR(100) NULL,
    bairro VARCHAR(100) NULL,
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

-- So depois daqui a tabela membros existe, entao o vinculo
-- users.membro_id (declarado la em cima, no 001) so pode ganhar sua
-- FOREIGN KEY aqui.
ALTER TABLE users
    ADD CONSTRAINT users_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS membro_documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    membro_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    arquivo_path VARCHAR(255) NOT NULL,
    tamanho_bytes INT UNSIGNED NOT NULL,
    enviado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT membro_documentos_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE CASCADE
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
    pix_chave VARCHAR(140) NULL,
    pix_nome_beneficiario VARCHAR(25) NULL,
    pix_mensagem_tipo ENUM('nenhuma', 'texto', 'versiculo') NOT NULL DEFAULT 'nenhuma',
    pix_mensagem_texto TEXT NULL,
    pix_mensagem_biblia_versao VARCHAR(10) NULL,
    pix_mensagem_livro_id TINYINT UNSIGNED NULL,
    pix_mensagem_capitulo SMALLINT UNSIGNED NULL,
    pix_mensagem_versiculo_inicio SMALLINT UNSIGNED NULL,
    pix_mensagem_versiculo_fim SMALLINT UNSIGNED NULL,
    cadastro_membros_habilitado TINYINT(1) NOT NULL DEFAULT 0,
    mensagem_aniversario TEXT NULL,
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

-- FK adicionada so agora porque biblia_livros precisa existir primeiro
-- (configuracoes_igreja e criada mais acima, ver migracao 034).
ALTER TABLE configuracoes_igreja
    ADD CONSTRAINT configuracoes_igreja_pix_mensagem_livro_id_foreign
        FOREIGN KEY (pix_mensagem_livro_id) REFERENCES biblia_livros (id) ON DELETE SET NULL;

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

CREATE TABLE IF NOT EXISTS projecao_imagens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_arquivo VARCHAR(190) NOT NULL,
    path VARCHAR(255) NOT NULL,
    favorita TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY projecao_imagens_favorita_index (favorita)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projecao_estados (
    sessao_id INT UNSIGNED PRIMARY KEY,
    modo ENUM('biblia', 'video', 'logo', 'blank', 'pix', 'imagem') NOT NULL DEFAULT 'blank',
    controlado_por ENUM('operador', 'preletor') NULL,
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
    video_volume TINYINT UNSIGNED NOT NULL DEFAULT 100,
    video_mudo TINYINT(1) NOT NULL DEFAULT 0,
    video_reiniciar_id INT UNSIGNED NOT NULL DEFAULT 0,
    pix_categoria VARCHAR(20) NULL,
    imagem_id INT UNSIGNED NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    leitura_id INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT projecao_estados_sessao_id_foreign
        FOREIGN KEY (sessao_id) REFERENCES projecao_sessoes (id) ON DELETE CASCADE,
    CONSTRAINT projecao_estados_livro_id_foreign
        FOREIGN KEY (livro_id) REFERENCES biblia_livros (id) ON DELETE SET NULL,
    CONSTRAINT projecao_estados_imagem_id_foreign
        FOREIGN KEY (imagem_id) REFERENCES projecao_imagens (id) ON DELETE SET NULL
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

-- 032 - Doacao via Pix estatico (chave da propria igreja, ver
-- Igrejas\Core\PixEstatico) - sem gateway, entao sem webhook: o doador
-- confirma manualmente que fez o Pix, o que gera o financeiro_lancamentos
-- correspondente (ver Igrejas\Models\FinanceiroDoacao::confirmar()).
CREATE TABLE IF NOT EXISTS financeiro_doacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_doador VARCHAR(150) NULL,
    categoria_id INT UNSIGNED NULL,
    valor DECIMAL(10,2) NOT NULL,
    mensagem VARCHAR(255) NULL,
    txid VARCHAR(25) NOT NULL UNIQUE,
    status ENUM('pendente', 'confirmada') NOT NULL DEFAULT 'pendente',
    lancamento_id INT UNSIGNED NULL,
    confirmada_em TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY financeiro_doacoes_status_index (status),
    CONSTRAINT financeiro_doacoes_categoria_id_foreign
        FOREIGN KEY (categoria_id) REFERENCES financeiro_categorias (id) ON DELETE SET NULL,
    CONSTRAINT financeiro_doacoes_lancamento_id_foreign
        FOREIGN KEY (lancamento_id) REFERENCES financeiro_lancamentos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
    visibilidade ENUM('publico', 'privado') NOT NULL DEFAULT 'publico',
    criado_por_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY agenda_eventos_data_index (data),
    KEY agenda_eventos_status_index (status),
    CONSTRAINT agenda_eventos_responsavel_membro_id_foreign
        FOREIGN KEY (responsavel_membro_id) REFERENCES membros (id) ON DELETE SET NULL,
    CONSTRAINT agenda_eventos_criado_por_user_id_foreign
        FOREIGN KEY (criado_por_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 044 - Aniversariantes: controle de envio do e-mail de parabens
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS aniversario_envios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    membro_id INT UNSIGNED NOT NULL,
    ano SMALLINT UNSIGNED NOT NULL,
    enviado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY aniversario_envios_unique (membro_id, ano),
    CONSTRAINT aniversario_envios_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 018 - Modulo Usuarios e Permissoes (a coluna "role" de "users" ja
-- nasce como ENUM, ver secao 001 no topo deste arquivo)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS user_modulos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    modulo_slug VARCHAR(50) NOT NULL,
    nivel ENUM('visualizar', 'editar') NOT NULL DEFAULT 'editar',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_modulos_unique (user_id, modulo_slug),
    CONSTRAINT user_modulos_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 045 - Permissoes: perfil padrao pra novos acessos
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS permissoes_padrao (
    modulo_slug VARCHAR(50) NOT NULL PRIMARY KEY,
    nivel ENUM('visualizar', 'editar') NOT NULL DEFAULT 'visualizar',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissoes_padrao (modulo_slug, nivel) VALUES
    ('agenda', 'visualizar'),
    ('equipe', 'visualizar'),
    ('cultos', 'visualizar'),
    ('ministerios', 'visualizar'),
    ('grupos', 'visualizar'),
    ('membros', 'visualizar'),
    ('playbacks', 'visualizar');


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


-- ----------------------------------------------------------------------------
-- 031 - Modulo Playbacks
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS playbacks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    artista VARCHAR(150) NULL,
    arquivo_path VARCHAR(255) NOT NULL,
    tamanho_bytes INT UNSIGNED NOT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY playbacks_titulo_index (titulo),
    KEY playbacks_status_index (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 039/040/041 - Modulo Louvores + Programacao de Culto (Modo Culto)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS louvores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    letra TEXT NULL,
    tom_atual VARCHAR(20) NULL,
    andamento_bpm SMALLINT UNSIGNED NULL,
    cifra TEXT NULL,
    anexo_path VARCHAR(255) NULL,
    anexo_nome_original VARCHAR(255) NULL,
    playback_id INT UNSIGNED NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    ultima_execucao TIMESTAMP NULL DEFAULT NULL,
    criado_por INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT louvores_playback_id_foreign
        FOREIGN KEY (playback_id) REFERENCES playbacks (id) ON DELETE SET NULL,
    CONSTRAINT louvores_criado_por_foreign
        FOREIGN KEY (criado_por) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS louvor_tons_historico (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    louvor_id INT UNSIGNED NOT NULL,
    tom_anterior VARCHAR(20) NULL,
    tom_novo VARCHAR(20) NOT NULL,
    observacao VARCHAR(255) NULL,
    alterado_por INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT louvor_tons_historico_louvor_id_foreign
        FOREIGN KEY (louvor_id) REFERENCES louvores (id) ON DELETE CASCADE,
    CONSTRAINT louvor_tons_historico_alterado_por_foreign
        FOREIGN KEY (alterado_por) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS louvor_anotacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    louvor_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    texto TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY louvor_anotacoes_unique (louvor_id, user_id),
    CONSTRAINT louvor_anotacoes_louvor_id_foreign
        FOREIGN KEY (louvor_id) REFERENCES louvores (id) ON DELETE CASCADE,
    CONSTRAINT louvor_anotacoes_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repertorios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    status ENUM('planejado', 'encerrado') NOT NULL DEFAULT 'planejado',
    atual_item_id INT UNSIGNED NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    criado_por INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT repertorios_criado_por_foreign
        FOREIGN KEY (criado_por) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repertorio_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repertorio_id INT UNSIGNED NOT NULL,
    louvor_id INT UNSIGNED NOT NULL,
    ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT repertorio_itens_repertorio_id_foreign
        FOREIGN KEY (repertorio_id) REFERENCES repertorios (id) ON DELETE CASCADE,
    CONSTRAINT repertorio_itens_louvor_id_foreign
        FOREIGN KEY (louvor_id) REFERENCES louvores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- So depois de repertorio_itens existir, pra poder referenciar.
ALTER TABLE repertorios
    ADD CONSTRAINT repertorios_atual_item_id_foreign
        FOREIGN KEY (atual_item_id) REFERENCES repertorio_itens (id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS repertorio_mensagens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repertorio_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    texto VARCHAR(280) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT repertorio_mensagens_repertorio_id_foreign
        FOREIGN KEY (repertorio_id) REFERENCES repertorios (id) ON DELETE CASCADE,
    CONSTRAINT repertorio_mensagens_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 049/050/051 - Modulo KADOSYS Kids (Fase 1 - operacional: turmas,
-- criancas e check-in/check-out do ministerio infantil, ja com colunas
-- minimas de gamificacao em kids_criancas - xp/moedas/sequencia,
-- incrementadas a cada check-in). Ver database/migrations/049 a 051
-- para o detalhamento de cada tabela.
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS kids_turmas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    faixa_etaria_min TINYINT UNSIGNED NULL,
    faixa_etaria_max TINYINT UNSIGNED NULL,
    professor_membro_id INT UNSIGNED NULL,
    descricao TEXT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY kids_turmas_status_index (status),
    CONSTRAINT kids_turmas_professor_membro_id_foreign
        FOREIGN KEY (professor_membro_id) REFERENCES membros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kids_criancas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    foto_path VARCHAR(255) NULL,
    data_nascimento DATE NULL,
    genero ENUM('masculino', 'feminino') NULL,
    turma_id INT UNSIGNED NULL,
    responsavel_membro_id INT UNSIGNED NULL,
    responsavel_nome VARCHAR(150) NULL,
    responsavel_telefone VARCHAR(20) NULL,
    autorizados_retirada TEXT NULL,
    alergias TEXT NULL,
    observacoes_medicas TEXT NULL,
    observacoes TEXT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    xp INT UNSIGNED NOT NULL DEFAULT 0,
    moedas INT UNSIGNED NOT NULL DEFAULT 0,
    sequencia_dias INT UNSIGNED NOT NULL DEFAULT 0,
    pin_hash VARCHAR(255) NULL,
    pin_definido_em TIMESTAMP NULL,
    pin_tentativas_invalidas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    pin_bloqueado_ate TIMESTAMP NULL,
    avatar_chapeu VARCHAR(40) NULL,
    avatar_acessorio VARCHAR(40) NULL,
    avatar_fundo VARCHAR(40) NULL,
    avatar_titulo VARCHAR(40) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY kids_criancas_status_index (status),
    KEY kids_criancas_turma_id_index (turma_id),
    CONSTRAINT kids_criancas_turma_id_foreign
        FOREIGN KEY (turma_id) REFERENCES kids_turmas (id) ON DELETE SET NULL,
    CONSTRAINT kids_criancas_responsavel_membro_id_foreign
        FOREIGN KEY (responsavel_membro_id) REFERENCES membros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kids_checkins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    data DATE NOT NULL,
    codigo_seguranca VARCHAR(6) NOT NULL,
    hora_entrada DATETIME NOT NULL,
    hora_saida DATETIME NULL,
    entregue_por VARCHAR(150) NULL,
    retirado_por VARCHAR(150) NULL,
    observacoes TEXT NULL,
    registrado_por_user_id INT UNSIGNED NULL,
    encerrado_por_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY kids_checkins_crianca_id_index (crianca_id),
    KEY kids_checkins_data_index (data),
    CONSTRAINT kids_checkins_crianca_id_foreign
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT kids_checkins_registrado_por_user_id_foreign
        FOREIGN KEY (registrado_por_user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT kids_checkins_encerrado_por_user_id_foreign
        FOREIGN KEY (encerrado_por_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 052/053 - Modulo KADOSYS Kids (Fase 2 - biblioteca de conteudo:
-- historias, videos, devocionais, quiz etc., com origem "kadosys"
-- (oficial, semeada abaixo, somente leitura pra igreja) ou "igreja"
-- (criado pela propria equipe em Kids > Conteudos). Ver
-- database/migrations/052 e 053 para o detalhamento de cada tabela.
-- ----------------------------------------------------------------------------

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

CREATE TABLE IF NOT EXISTS kids_conteudo_conclusoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    conteudo_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY kids_conteudo_conclusoes_unique (crianca_id, conteudo_id),
    CONSTRAINT kids_conteudo_conclusoes_crianca_id_foreign
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT kids_conteudo_conclusoes_conteudo_id_foreign
        FOREIGN KEY (conteudo_id) REFERENCES kids_conteudos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Biblioteca oficial KADOSYS: um punhado de conteudos de exemplo,
-- variados por tipo, ja disponiveis pra toda igreja nova assim que o
-- banco e provisionado (origem 'kadosys' - somente leitura pra
-- igreja, ver KidsConteudoController). A equipe KADOSYS pode
-- adicionar mais conteudos rodando o mesmo INSERT no banco de cada
-- igreja ja existente (nao ha ainda um mecanismo central de
-- replicacao automatica - ver observacao no PR).
INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('historia', 'kadosys', 'A Arca de Noé', 'Deus pede a Noé para construir uma arca gigante antes do grande dilúvio.', 'Velho Testamento', 'Obediência', 'Gênesis', 'Noé', 'facil', 4, 8, 8, 15, 8,
     'Há muito tempo, Deus viu que as pessoas na Terra estavam fazendo coisas erradas. Mas havia um homem chamado Noé que amava a Deus. Deus pediu que Noé construísse uma arca enorme, de madeira, para salvar sua família e dois de cada animal do mundo. Noé obedeceu, mesmo sem entender tudo. Choveu por 40 dias e 40 noites, mas a arca flutuou e todos ficaram em segurança. Quando a chuva parou, Deus colocou um arco-íris no céu, prometendo nunca mais destruir a Terra com um dilúvio. Essa é a promessa do arco-íris até hoje!',
     NULL, 'publicado', NOW()),
    ('video', 'kadosys', 'Davi e Golias', 'O jovem pastor Davi enfrenta o gigante Golias confiando em Deus.', 'Velho Testamento', 'Coragem', '1 Samuel', 'Davi', 'facil', 6, 10, 5, 15, 8,
     'Adicione o link do vídeo em Kids > Conteúdos > Editar.', NULL, 'publicado', NOW()),
    ('devocional', 'kadosys', 'Deus cuida de mim', 'Um devocional curto sobre o cuidado de Deus com cada criança.', 'Valores', 'Cuidado de Deus', NULL, NULL, 'facil', 3, 6, 3, 10, 5,
     'Você sabia que Deus conhece até quantos fios de cabelo você tem na cabeça? Ele te ama tanto que cuida de cada detalhezinho da sua vida! Quando você acorda de manhã, Deus está com você. Quando você brinca, Deus está com você. Quando você tem medo, pode conversar com Ele, porque Ele sempre escuta. Hoje, agradeça a Deus por cuidar de você o dia inteiro!',
     NULL, 'publicado', NOW()),
    ('quiz', 'kadosys', 'Quiz: Personagens da Bíblia', 'Teste o que você sabe sobre os grandes personagens bíblicos!', 'Geral', 'Personagens', NULL, NULL, 'medio', 6, 10, 5, 20, 10,
     NULL,
     '[{"pergunta":"Quem construiu a arca?","alternativas":["Moisés","Noé","Davi","Abraão"],"correta":1},{"pergunta":"Quantos dias e noites choveu no dilúvio?","alternativas":["7","40","100","3"],"correta":1},{"pergunta":"Quem derrotou o gigante Golias?","alternativas":["Saul","Salomão","Davi","Sansão"],"correta":2}]',
     'publicado', NOW()),
    ('colorir', 'kadosys', 'Jonas e a Baleia', 'Desenho para colorir da história de Jonas dentro do grande peixe.', 'Velho Testamento', 'Obediência', 'Jonas', 'Jonas', 'facil', 3, 8, NULL, 10, 5,
     NULL, NULL, 'publicado', NOW()),
    ('versiculo_ilustrado', 'kadosys', 'João 3:16', 'O versículo mais conhecido da Bíblia, em linguagem para crianças.', 'Novo Testamento', 'Amor de Deus', 'João', 'Jesus', 'facil', 4, 10, 2, 10, 5,
     '"Porque Deus amou tanto o mundo, que deu o seu único Filho, para que todo aquele que nele crê não pereça, mas tenha a vida eterna." João 3:16',
     NULL, 'publicado', NOW()),
    ('desafio', 'kadosys', 'Desafio da Semana: Ore por alguém', 'Um desafio simples para praticar o amor ao próximo.', 'Missões', 'Oração', NULL, NULL, 'facil', 5, 12, NULL, 20, 10,
     'Esta semana, escolha uma pessoa da sua família ou um amigo e ore por ela todos os dias. Pode ser um pedido de saúde, de alegria ou só agradecendo por essa pessoa existir. No fim da semana, conte pra ela que você orou!',
     NULL, 'publicado', NOW()),
    ('estudo', 'kadosys', 'Os frutos do Espírito', 'Um estudo simples sobre as qualidades que Deus quer desenvolver em nós.', 'Novo Testamento', 'Caráter cristão', 'Gálatas', NULL, 'medio', 7, 12, 10, 15, 8,
     'A Bíblia ensina que, quando deixamos o Espírito Santo agir em nossa vida, alguns "frutos" aparecem: amor, alegria, paz, paciência, amabilidade, bondade, fidelidade, mansidão e domínio próprio (Gálatas 5:22-23). Assim como uma árvore boa dá frutos bons, uma vida com Deus produz essas qualidades. Qual desses frutos você quer pedir a Deus para crescer mais em você esta semana?',
     NULL, 'publicado', NOW());

-- Biblioteca oficial KADOSYS (continuação): mais conteudos, cobrindo
-- os tipos que ainda nao tinham nenhum exemplo (slide, hq,
-- plano_leitura, pdf, atividade, jogo) - ver database/migrations/055.
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
     NULL, 'publicado', NOW()),

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
