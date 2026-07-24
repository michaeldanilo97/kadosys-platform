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
    sequencia_app_dias INT UNSIGNED NOT NULL DEFAULT 0,
    ultima_visita_app_em DATE NULL,
    pin_hash VARCHAR(255) NULL,
    pin_definido_em TIMESTAMP NULL,
    pin_tentativas_invalidas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    pin_bloqueado_ate TIMESTAMP NULL,
    avatar_chapeu VARCHAR(40) NULL,
    avatar_acessorio VARCHAR(40) NULL,
    avatar_fundo VARCHAR(40) NULL,
    avatar_titulo VARCHAR(40) NULL,
    avatar_pele VARCHAR(40) NULL,
    avatar_roupa VARCHAR(40) NULL,
    avatar_mascote VARCHAR(20) NOT NULL DEFAULT 'leao',
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
    foto_path VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY kids_conteudo_conclusoes_unique (crianca_id, conteudo_id),
    CONSTRAINT kids_conteudo_conclusoes_crianca_id_foreign
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT kids_conteudo_conclusoes_conteudo_id_foreign
        FOREIGN KEY (conteudo_id) REFERENCES kids_conteudos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kids_missoes_diarias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    data DATE NOT NULL,
    conteudo_id INT UNSIGNED NOT NULL,
    concluida_em TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_kids_missoes_diarias (crianca_id, data, conteudo_id),
    CONSTRAINT fk_kids_missoes_diarias_crianca
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT fk_kids_missoes_diarias_conteudo
        FOREIGN KEY (conteudo_id) REFERENCES kids_conteudos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kids_duelos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conteudo_id INT UNSIGNED NOT NULL,
    criador_id INT UNSIGNED NOT NULL,
    convidado_id INT UNSIGNED NOT NULL,
    status ENUM('pendente', 'aceito', 'recusado', 'em_andamento', 'finalizado') NOT NULL DEFAULT 'pendente',
    criador_progresso INT UNSIGNED NOT NULL DEFAULT 0,
    convidado_progresso INT UNSIGNED NOT NULL DEFAULT 0,
    criador_terminado_em TIMESTAMP NULL,
    convidado_terminado_em TIMESTAMP NULL,
    vencedor_id INT UNSIGNED NULL,
    reacao_criador VARCHAR(8) NULL,
    reacao_criador_em TIMESTAMP NULL,
    reacao_convidado VARCHAR(8) NULL,
    reacao_convidado_em TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY kids_duelos_convidado_status_index (convidado_id, status),
    KEY kids_duelos_criador_status_index (criador_id, status),
    CONSTRAINT fk_kids_duelos_conteudo
        FOREIGN KEY (conteudo_id) REFERENCES kids_conteudos (id) ON DELETE CASCADE,
    CONSTRAINT fk_kids_duelos_criador
        FOREIGN KEY (criador_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT fk_kids_duelos_convidado
        FOREIGN KEY (convidado_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT fk_kids_duelos_vencedor
        FOREIGN KEY (vencedor_id) REFERENCES kids_criancas (id) ON DELETE SET NULL
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

-- Biblioteca oficial KADOSYS: substitui os itens que so tinham
-- placeholder ("Adicione o arquivo em Kids > Conteudos > Editar")
-- por conteudo de verdade (colorir/jogo/slide/hq interativos, PDFs
-- reais) e remove os 8 itens de video/audio sem link disponivel -
-- equivalente a database/migrations/058_kids_conteudos_reais.sql.
UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 50,260 A 150,150 0 0 1 350,260 L 320,260 A 120,120 0 0 0 80,260 Z"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 80,260 A 120,120 0 0 1 320,260 L 290,260 A 90,90 0 0 0 110,260 Z"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 110,260 A 90,90 0 0 1 290,260 L 260,260 A 60,60 0 0 0 140,260 Z"/>
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 40,278 Q 70,268 100,278 T 160,278 T 220,278 T 280,278 T 330,278"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 110,230 L 290,230 L 260,262 L 140,262 Z"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="170" y="188" width="60" height="42" rx="8"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 163,190 L 200,163 L 237,190 Z"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="200" cy="210" r="10"/>
<ellipse class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="315" cy="130" rx="24" ry="16"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 296,126 L 268,108 L 300,142 Z"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="338" cy="122" r="9"/>
<circle cx="335" cy="120" r="2" fill="#3A2E5C"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'Noé e os Animais na Arca';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="330" cy="60" r="28"/>
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 330,20 L 330,8 M 362,32 L 372,24 M 374,60 L 386,60 M 362,88 L 372,96 M 298,32 L 288,24"/>
<path fill="none" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 55,270 L 55,110 Q 55,88 78,88 Q 100,88 100,110"/>
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 40,280 L 320,280"/>
<ellipse class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="190" cy="205" rx="72" ry="46"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="268" cy="192" r="30"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="252" cy="164" r="11"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="286" cy="164" r="11"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="140" y="240" width="14" height="30" rx="5"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="175" y="245" width="14" height="30" rx="5"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="210" y="245" width="14" height="30" rx="5"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="240" y="240" width="14" height="30" rx="5"/>
<circle cx="278" cy="188" r="2.5" fill="#3A2E5C"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'Jesus, o Bom Pastor';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 200,55 L 214,100 L 260,100 L 224,127 L 238,172 L 200,145 L 162,172 L 176,127 L 140,100 L 186,100 Z"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="4" stroke-linejoin="round" d="M 305,55 L 310,70 L 325,75 L 310,80 L 305,95 L 300,80 L 285,75 L 300,70 Z"/>
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 30,280 L 370,280"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 148,262 L 200,208 L 252,262 Z"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="158" y="262" width="84" height="42"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="190" y="282" width="20" height="22"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'A Estrela de Belém';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 30,280 L 370,280"/>
<line x1="90" y1="215" x2="130" y2="215" stroke="#3A2E5C" stroke-width="8" stroke-linecap="round"/>
<line x1="270" y1="215" x2="310" y2="215" stroke="#3A2E5C" stroke-width="8" stroke-linecap="round"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 140,158 Q 108,120 150,108 Q 162,130 150,158 Z"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 260,158 Q 292,120 250,108 Q 238,130 250,158 Z"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="118" y="152" width="164" height="22" rx="6"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="130" y="172" width="140" height="92" rx="8"/>
<line x1="130" y1="205" x2="270" y2="205" stroke="#3A2E5C" stroke-width="4"/>
<line x1="130" y1="235" x2="270" y2="235" stroke="#3A2E5C" stroke-width="4"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'A Arca da Aliança';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 200,225 C 160,188 92,150 92,107 C 92,72 120,52 150,52 C 175,52 195,66 200,90 C 205,66 225,52 250,52 C 280,52 308,72 308,107 C 308,150 240,188 200,225 Z"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="190" y="90" width="20" height="88" rx="4"/>
<rect class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" x="160" y="114" width="80" height="20" rx="4"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="62" cy="245" r="27"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="338" cy="245" r="27"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="200" cy="270" r="27"/>
<circle cx="53" cy="240" r="2.4" fill="#3A2E5C"/><circle cx="71" cy="240" r="2.4" fill="#3A2E5C"/>
<circle cx="329" cy="240" r="2.4" fill="#3A2E5C"/><circle cx="347" cy="240" r="2.4" fill="#3A2E5C"/>
<circle cx="191" cy="265" r="2.4" fill="#3A2E5C"/><circle cx="209" cy="265" r="2.4" fill="#3A2E5C"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'Jesus abençoa as crianças';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 30,282 L 370,282"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 150,55 L 250,55 L 250,140 Q 250,205 150,232 Q 50,205 50,140 L 50,55 Z"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="150" cy="140" r="26"/>
<line x1="330" y1="55" x2="330" y2="85" stroke="#3A2E5C" stroke-width="6" stroke-linecap="round"/>
<line x1="330" y1="85" x2="298" y2="135" stroke="#3A2E5C" stroke-width="6" stroke-linecap="round"/>
<line x1="330" y1="85" x2="362" y2="135" stroke="#3A2E5C" stroke-width="6" stroke-linecap="round"/>
<ellipse class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="330" cy="150" rx="15" ry="9"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="70" cy="262" r="11"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="105" cy="262" r="11"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="140" cy="262" r="11"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="175" cy="262" r="11"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="210" cy="262" r="11"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'Davi e Golias';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-colorir" data-colorir>
<svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg" class="kids-colorir-svg">
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 20,268 Q 50,258 80,268 T 140,268 T 200,268 T 260,268 T 320,268 T 380,268"/>
<ellipse class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="210" cy="175" rx="145" ry="72"/>
<path class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" d="M 68,175 L 24,133 L 42,175 L 24,217 Z"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="230" cy="93" r="9"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="247" cy="76" r="7"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="259" cy="60" r="5"/>
<circle cx="302" cy="150" r="6" fill="#3A2E5C"/>
<path fill="none" stroke="#3A2E5C" stroke-width="4" stroke-linecap="round" d="M 322,168 Q 335,175 322,182"/>
<circle class="col" fill="#FFFFFF" stroke="#3A2E5C" stroke-width="6" stroke-linejoin="round" stroke-linecap="round" cx="205" cy="178" r="40"/>
<circle cx="205" cy="164" r="7" fill="none" stroke="#3A2E5C" stroke-width="3"/>
<path fill="none" stroke="#3A2E5C" stroke-width="3" stroke-linecap="round" d="M 205,171 L 205,196 M 205,177 L 191,163 M 205,177 L 219,163"/>
</svg>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-cor" data-cor="#8B5A2B" style="background-color:#8B5A2B;" aria-label="Marrom"></button>
<button type="button" class="kids-colorir-limpar" data-colorir-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-colorir]'').forEach(function (palco) {
        var svg = palco.querySelector(''svg'');
        var swatches = palco.querySelectorAll(''[data-cor]'');
        var ativa = palco.querySelector(''.kids-colorir-cor.ativa'');
        var atual = ativa ? ativa.getAttribute(''data-cor'') : ''#FF6B6B'';
        swatches.forEach(function (sw) {
            sw.addEventListener(''click'', function () {
                swatches.forEach(function (s) { s.classList.remove(''ativa''); });
                sw.classList.add(''ativa'');
                atual = sw.getAttribute(''data-cor'');
            });
        });
        svg.querySelectorAll(''.col'').forEach(function (parte) {
            parte.addEventListener(''click'', function () {
                parte.setAttribute(''fill'', atual);
            });
        });
        var limpar = palco.querySelector(''[data-colorir-limpar]'');
        if (limpar) {
            limpar.addEventListener(''click'', function () {
                svg.querySelectorAll(''.col'').forEach(function (parte) { parte.setAttribute(''fill'', ''#FFFFFF''); });
            });
        }
    });
})();
</script>'
    WHERE tipo = 'colorir' AND origem = 'kadosys' AND titulo = 'Jonas e a Baleia';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-jogo-memoria" data-memoria>
<p class="kids-jogo-status" data-memoria-status>Encontre os pares de animais da arca! 🐾</p>
<div class="kids-memoria-grade" data-memoria-grade>
<button type="button" class="kids-memoria-carta" data-emoji="🐘">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦒">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦁">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐯">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐻">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐵">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦓">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦌">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐘">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦒">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦁">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐯">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐻">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐵">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦓">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🦌">❓</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-memoria]'').forEach(function (jogo) {
        var grade = jogo.querySelector(''[data-memoria-grade]'');
        var status = jogo.querySelector(''[data-memoria-status]'');
        var cartas = Array.prototype.slice.call(grade.children);

        for (var i = cartas.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = cartas[i]; cartas[i] = cartas[j]; cartas[j] = tmp;
        }
        cartas.forEach(function (c) { grade.appendChild(c); });

        var virada = null;
        var travado = false;
        var pares = 0;
        var totalPares = cartas.length / 2;

        cartas.forEach(function (carta) {
            carta.addEventListener(''click'', function () {
                if (travado || carta.classList.contains(''virada'') || carta.classList.contains(''encontrada'')) {
                    return;
                }

                carta.classList.add(''virada'');
                carta.textContent = carta.getAttribute(''data-emoji'');

                if (!virada) {
                    virada = carta;
                    return;
                }

                travado = true;

                if (virada.getAttribute(''data-emoji'') === carta.getAttribute(''data-emoji'')) {
                    virada.classList.add(''encontrada'');
                    carta.classList.add(''encontrada'');
                    pares++;
                    virada = null;
                    travado = false;

                    if (pares === totalPares) {
                        status.textContent = ''Você encontrou todos os pares! 🎉'';
                    }
                } else {
                    setTimeout(function () {
                        virada.classList.remove(''virada'');
                        virada.textContent = ''❓'';
                        carta.classList.remove(''virada'');
                        carta.textContent = ''❓'';
                        virada = null;
                        travado = false;
                    }, 700);
                }
            });
        });
    });
})();
</script>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Monte a Arca de Noé';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-corrida" data-corrida>
<div class="kids-corrida-cabecalho">
<span>🏁 Sua trilha da fé</span>
<span class="kids-corrida-estrelas" data-corrida-estrelas>⭐ 0/8</span>
</div>
<div class="kids-quiz-pergunta">
<p>1. Quantos discípulos Jesus escolheu?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">12</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">7</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">20</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">3</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>2. Quem escreveu muitos Salmos?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Davi</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Golias</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Faraó</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Herodes</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>3. Em que cidade Jesus nasceu?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Belém</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Nazaré</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Jerusalém</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Roma</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>4. Quem foi engolido por um grande peixe?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Jonas</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Pedro</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Paulo</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Elias</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>5. Quantos dias Deus levou pra criar o mundo?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">6</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">3</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">40</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">10</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>6. Quem atravessou o Mar Vermelho com o povo de Israel?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Moisés</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Josué</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Davi</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Sansão</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>7. Qual é o primeiro livro da Bíblia?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Gênesis</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Êxodo</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Salmos</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Mateus</button>
</div>
</div>
<div class="kids-quiz-pergunta">
<p>8. Quem traiu Jesus por 30 moedas de prata?</p>
<div class="kids-quiz-alternativas" data-quiz-alternativas>
<button type="button" class="kids-quiz-alternativa" data-correta="1">Judas</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Pedro</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">Tomé</button>
<button type="button" class="kids-quiz-alternativa" data-correta="0">João</button>
</div>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-corrida]'').forEach(function (corrida) {
        var contador = corrida.querySelector(''[data-corrida-estrelas]'');
        var grupos = corrida.querySelectorAll(''[data-quiz-alternativas]'');
        var total = grupos.length;
        var acertos = 0;

        grupos.forEach(function (grupo) {
            grupo.addEventListener(''click'', function (event) {
                var escolhida = event.target.closest(''.kids-quiz-alternativa'');

                if (!escolhida || grupo.classList.contains(''respondida'')) {
                    return;
                }

                grupo.classList.add(''respondida'');
                var acertou = escolhida.getAttribute(''data-correta'') === ''1'';

                grupo.querySelectorAll(''.kids-quiz-alternativa'').forEach(function (botao) {
                    botao.disabled = true;

                    if (botao.getAttribute(''data-correta'') === ''1'') {
                        botao.classList.add(''correta'');
                    } else if (botao === escolhida) {
                        botao.classList.add(''errada'');
                    }
                });

                if (acertou) {
                    acertos++;

                    if (contador) {
                        contador.textContent = ''⭐ '' + acertos + ''/'' + total;
                    }
                }
            });
        });
    });
})();
</script>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Corrida da Fé';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-jogo-memoria" data-memoria>
<p class="kids-jogo-status" data-memoria-status>Encontre os pares de símbolos da fé! ✨</p>
<div class="kids-memoria-grade" data-memoria-grade>
<button type="button" class="kids-memoria-carta" data-emoji="📖">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="✝️">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🕊️">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="⭐">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🙏">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="👑">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🌈">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐑">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="📖">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="✝️">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🕊️">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="⭐">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🙏">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="👑">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🌈">❓</button>
<button type="button" class="kids-memoria-carta" data-emoji="🐑">❓</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-memoria]'').forEach(function (jogo) {
        var grade = jogo.querySelector(''[data-memoria-grade]'');
        var status = jogo.querySelector(''[data-memoria-status]'');
        var cartas = Array.prototype.slice.call(grade.children);

        for (var i = cartas.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = cartas[i]; cartas[i] = cartas[j]; cartas[j] = tmp;
        }
        cartas.forEach(function (c) { grade.appendChild(c); });

        var virada = null;
        var travado = false;
        var pares = 0;
        var totalPares = cartas.length / 2;

        cartas.forEach(function (carta) {
            carta.addEventListener(''click'', function () {
                if (travado || carta.classList.contains(''virada'') || carta.classList.contains(''encontrada'')) {
                    return;
                }

                carta.classList.add(''virada'');
                carta.textContent = carta.getAttribute(''data-emoji'');

                if (!virada) {
                    virada = carta;
                    return;
                }

                travado = true;

                if (virada.getAttribute(''data-emoji'') === carta.getAttribute(''data-emoji'')) {
                    virada.classList.add(''encontrada'');
                    carta.classList.add(''encontrada'');
                    pares++;
                    virada = null;
                    travado = false;

                    if (pares === totalPares) {
                        status.textContent = ''Você encontrou todos os pares! 🎉'';
                    }
                } else {
                    setTimeout(function () {
                        virada.classList.remove(''virada'');
                        virada.textContent = ''❓'';
                        carta.classList.remove(''virada'');
                        carta.textContent = ''❓'';
                        virada = null;
                        travado = false;
                    }, 700);
                }
            });
        });
    });
})();
</script>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Memória Bíblica';

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
</div>
<script>
(function () {
    document.querySelectorAll(''[data-slides]'').forEach(function (container) {
        var slides = Array.prototype.slice.call(container.querySelectorAll(''[data-slide]''));
        var contador = container.querySelector(''[data-slide-contador]'');
        var indice = 0;

        function mostrar(novoIndice) {
            slides[indice].classList.remove(''is-ativo'');
            indice = novoIndice;
            slides[indice].classList.add(''is-ativo'');
            contador.textContent = (indice + 1) + '' / '' + slides.length;
        }

        var anterior = container.querySelector(''[data-slide-prev]'');
        var proxima = container.querySelector(''[data-slide-next]'');

        if (anterior) {
            anterior.addEventListener(''click'', function () {
                mostrar((indice - 1 + slides.length) % slides.length);
            });
        }

        if (proxima) {
            proxima.addEventListener(''click'', function () {
                mostrar((indice + 1) % slides.length);
            });
        }
    });
})();
</script>'
    WHERE tipo = 'slide' AND origem = 'kadosys' AND titulo = 'Os 12 Discípulos de Jesus';

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
</div>
<script>
(function () {
    document.querySelectorAll(''[data-slides]'').forEach(function (container) {
        var slides = Array.prototype.slice.call(container.querySelectorAll(''[data-slide]''));
        var contador = container.querySelector(''[data-slide-contador]'');
        var indice = 0;

        function mostrar(novoIndice) {
            slides[indice].classList.remove(''is-ativo'');
            indice = novoIndice;
            slides[indice].classList.add(''is-ativo'');
            contador.textContent = (indice + 1) + '' / '' + slides.length;
        }

        var anterior = container.querySelector(''[data-slide-prev]'');
        var proxima = container.querySelector(''[data-slide-next]'');

        if (anterior) {
            anterior.addEventListener(''click'', function () {
                mostrar((indice - 1 + slides.length) % slides.length);
            });
        }

        if (proxima) {
            proxima.addEventListener(''click'', function () {
                mostrar((indice + 1) % slides.length);
            });
        }
    });
})();
</script>'
    WHERE tipo = 'slide' AND origem = 'kadosys' AND titulo = 'Mapa da Terra Santa para Crianças';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-hq">
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">👦😢</div>
<p class="kids-hq-legenda">José era o filho caçula preferido do pai, e seus irmãos ficaram cheios de inveja.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">🕳️👋</div>
<p class="kids-hq-legenda">Tomados pela raiva, os irmãos jogaram José num poço e depois o venderam como escravo.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-rosa);">
<div class="kids-hq-cena">🐫➡️</div>
<p class="kids-hq-legenda">José foi levado para o Egito - mas Deus estava com ele em cada passo do caminho.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-verde);">
<div class="kids-hq-cena">⛓️😔</div>
<p class="kids-hq-legenda">Mesmo acusado injustamente e preso, José continuou confiando em Deus.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-roxo);">
<div class="kids-hq-cena">💭✨</div>
<p class="kids-hq-legenda">José tinha um dom especial: interpretar sonhos, com a ajuda de Deus.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-laranja);">
<div class="kids-hq-cena">👑🎉</div>
<p class="kids-hq-legenda">O Faraó ficou tão impressionado que fez José governador de todo o Egito!</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">🌾</div>
<p class="kids-hq-legenda">José guardou comida durante os anos bons, salvando o Egito de uma grande fome.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">🤝😭</div>
<p class="kids-hq-legenda">Anos depois, seus irmãos foram pedir ajuda sem saber quem ele era - e José os perdoou com um abraço.</p>
</div>
</div>'
    WHERE tipo = 'hq' AND origem = 'kadosys' AND titulo = 'As Aventuras de José no Egito';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-hq">
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">🕌🙏</div>
<p class="kids-hq-legenda">Daniel vivia na Babilônia, mas nunca deixou de orar ao seu Deus, três vezes por dia.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">📜🚫</div>
<p class="kids-hq-legenda">Uns homens maus fizeram uma lei proibindo orar a qualquer um, menos ao rei.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-rosa);">
<div class="kids-hq-cena">🙏😌</div>
<p class="kids-hq-legenda">Mesmo sabendo do perigo, Daniel continuou orando a Deus como sempre fazia.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-verde);">
<div class="kids-hq-cena">🦁🦁🦁</div>
<p class="kids-hq-legenda">Por desobedecer a lei, Daniel foi jogado numa cova cheia de leões famintos.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-roxo);">
<div class="kids-hq-cena">👼🛡️</div>
<p class="kids-hq-legenda">Mas Deus enviou um anjo para fechar a boca dos leões a noite inteira.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-laranja);">
<div class="kids-hq-cena">🎉🙌</div>
<p class="kids-hq-legenda">Na manhã seguinte, Daniel saiu são e salvo - e todos viram o poder de Deus!</p>
</div>
</div>'
    WHERE tipo = 'hq' AND origem = 'kadosys' AND titulo = 'Daniel, o Homem Corajoso';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-hq">
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">⭐🐑</div>
<p class="kids-hq-legenda">Jesus nasceu em Belém numa noite marcada por uma estrela brilhante no céu.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">💧🕊️</div>
<p class="kids-hq-legenda">Quando adulto, foi batizado por João no Rio Jordão, e o Espírito de Deus desceu sobre Ele.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-rosa);">
<div class="kids-hq-cena">✋🍞🐟</div>
<p class="kids-hq-legenda">Jesus fazia milagres: curava doentes e alimentou 5 mil pessoas com só 5 pães e 2 peixes!</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-verde);">
<div class="kids-hq-cena">👨‍👩‍👧‍👦❤️</div>
<p class="kids-hq-legenda">Ele ensinava sobre o amor de Deus e como cuidar uns dos outros.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-roxo);">
<div class="kids-hq-cena">✝️😢</div>
<p class="kids-hq-legenda">Jesus foi preso injustamente e morreu na cruz para pagar pelos nossos pecados.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-laranja);">
<div class="kids-hq-cena">🌅🎉</div>
<p class="kids-hq-legenda">Mas no terceiro dia, Ele ressuscitou! A morte não pôde detê-Lo!</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-amarelo);">
<div class="kids-hq-cena">☁️🙌</div>
<p class="kids-hq-legenda">Depois, Jesus subiu ao céu, prometendo voltar um dia.</p>
</div>
<div class="kids-hq-painel" style="--cor-painel: var(--kids-azul);">
<div class="kids-hq-cena">❤️🌍</div>
<p class="kids-hq-legenda">E até hoje, Ele convida cada criança a fazer parte da sua família para sempre.</p>
</div>
</div>'
    WHERE tipo = 'hq' AND origem = 'kadosys' AND titulo = 'A Vida de Jesus em Quadrinhos';

UPDATE kids_conteudos SET texto_conteudo = 'Brinque em casa: escreva os 9 frutos do Espírito (amor, alegria, paz, paciência, amabilidade, bondade, fidelidade, mansidão e domínio próprio) em pedacinhos de papel e sorteie um por vez, contando uma situação em que você pode praticar aquele fruto hoje.'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Bingo dos Frutos do Espírito';

UPDATE kids_conteudos SET midia_path = 'assets/kids/pdfs/10-mandamentos.pdf', texto_conteudo = NULL
    WHERE tipo = 'pdf' AND origem = 'kadosys' AND titulo = 'Caderno de Atividades: Os 10 Mandamentos';

UPDATE kids_conteudos SET midia_path = 'assets/kids/pdfs/livros-da-biblia.pdf', texto_conteudo = NULL
    WHERE tipo = 'pdf' AND origem = 'kadosys' AND titulo = 'Cartilha: Livros da Bíblia';

UPDATE kids_conteudos SET midia_path = 'assets/kids/pdfs/diploma-kids.pdf', texto_conteudo = NULL
    WHERE tipo = 'pdf' AND origem = 'kadosys' AND titulo = 'Diploma Kids KADOSYS';

-- Remove os 8 placeholders de video/audio (sem link real disponivel).
DELETE FROM kids_conteudos WHERE tipo = 'video' AND origem = 'kadosys' AND titulo = 'Davi e Golias';
DELETE FROM kids_conteudos WHERE tipo = 'video' AND origem = 'kadosys' AND titulo = 'A Parábola do Filho Pródigo';
DELETE FROM kids_conteudos WHERE tipo = 'video' AND origem = 'kadosys' AND titulo = 'Daniel na Cova dos Leões';
DELETE FROM kids_conteudos WHERE tipo = 'video' AND origem = 'kadosys' AND titulo = 'Davi Tocando Harpa para o Rei Saul';
DELETE FROM kids_conteudos WHERE tipo = 'video' AND origem = 'kadosys' AND titulo = 'A Criação em 7 Dias';
DELETE FROM kids_conteudos WHERE tipo = 'audio' AND origem = 'kadosys' AND titulo = 'Salmo 23 narrado para crianças';
DELETE FROM kids_conteudos WHERE tipo = 'audio' AND origem = 'kadosys' AND titulo = 'Louvor Infantil: Deus é Bom';
DELETE FROM kids_conteudos WHERE tipo = 'audio' AND origem = 'kadosys' AND titulo = 'Salmo 100 narrado para crianças';

-- Migration 059 (caca-palavras) - ja incluida aqui pra instalacoes
-- novas partirem direto com esses puzzles no catalogo oficial.
-- Gerado por gerar_caca_palavras.php - nao editar a mao.
INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Caça-Nomes: Os 12 Discípulos', 'Ache o nome dos 12 discípulos escondidos na grade de letras.', 'Novo Testamento', 'Discípulos', 'Mateus', NULL, 'medio', 6, 12, 10, 15, 8,
     '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">N</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="PEDRO">PEDRO</span>
<span class="kids-cp-palavra" data-cp-palavra="ANDRÉ">ANDRÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="TIAGO">TIAGO</span>
<span class="kids-cp-palavra" data-cp-palavra="JOÃO">JOÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="FILIPE">FILIPE</span>
<span class="kids-cp-palavra" data-cp-palavra="TOMÉ">TOMÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="MATEUS">MATEUS</span>
<span class="kids-cp-palavra" data-cp-palavra="JUDAS">JUDAS</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"FILIPE","cells":[[5,3],[4,3],[3,3],[2,3],[1,3],[0,3]],"achada":false},{"word":"MATEUS","cells":[[8,1],[8,2],[8,3],[8,4],[8,5],[8,6]],"achada":false},{"word":"PEDRO","cells":[[1,4],[2,4],[3,4],[4,4],[5,4]],"achada":false},{"word":"ANDRÉ","cells":[[3,8],[4,7],[5,6],[6,5],[7,4]],"achada":false},{"word":"TIAGO","cells":[[9,4],[9,5],[9,6],[9,7],[9,8]],"achada":false},{"word":"JUDAS","cells":[[4,0],[3,0],[2,0],[1,0],[0,0]],"achada":false},{"word":"JOÃO","cells":[[1,2],[2,2],[3,2],[4,2]],"achada":false},{"word":"TOMÉ","cells":[[2,8],[3,7],[4,6],[5,5]],"achada":false}]</script>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-cacapalavras]'').forEach(function (jogo) {
        var dadosEl = jogo.querySelector(''[data-cp-dados]'');
        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector(''[data-cp-status]'');
        var total = palavras.length;
        var encontradas = 0;
        var inicio = null;

        function celulaEm(r, c) {
            return jogo.querySelector(''[data-r="'' + r + ''"][data-c="'' + c + ''"]'');
        }

        function limparSelecao() {
            jogo.querySelectorAll(''.selecionada'').forEach(function (el) {
                el.classList.remove(''selecionada'');
            });
        }

        function caminhoEntre(r1, c1, r2, c2) {
            var dr = r2 - r1;
            var dc = c2 - c1;
            var passos = Math.max(Math.abs(dr), Math.abs(dc));

            if (passos === 0) {
                return null;
            }

            if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) {
                return null;
            }

            var stepR = dr === 0 ? 0 : dr / Math.abs(dr);
            var stepC = dc === 0 ? 0 : dc / Math.abs(dc);
            var caminho = [];

            for (var i = 0; i <= passos; i++) {
                caminho.push([r1 + stepR * i, c1 + stepC * i]);
            }

            return caminho;
        }

        function caminhosIguais(a, b) {
            if (a.length !== b.length) {
                return false;
            }

            var direto = a.every(function (p, i) { return p[0] === b[i][0] && p[1] === b[i][1]; });
            var reverso = a.every(function (p, i) { return p[0] === b[b.length - 1 - i][0] && p[1] === b[b.length - 1 - i][1]; });

            return direto || reverso;
        }

        jogo.querySelectorAll(''[data-cp-celula]'').forEach(function (celula) {
            celula.addEventListener(''click'', function () {
                if (celula.classList.contains(''encontrada'')) {
                    return;
                }

                var r = parseInt(celula.getAttribute(''data-r''), 10);
                var c = parseInt(celula.getAttribute(''data-c''), 10);

                if (!inicio) {
                    limparSelecao();
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var caminho = caminhoEntre(inicio.r, inicio.c, r, c);
                limparSelecao();

                if (!caminho) {
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var acertou = null;

                palavras.forEach(function (p) {
                    if (!p.achada && caminhosIguais(caminho, p.cells)) {
                        acertou = p;
                    }
                });

                if (acertou) {
                    acertou.achada = true;
                    encontradas++;

                    caminho.forEach(function (pos) {
                        var el = celulaEm(pos[0], pos[1]);

                        if (el) {
                            el.classList.add(''encontrada'');
                        }
                    });

                    var chip = jogo.querySelector(''[data-cp-palavra="'' + acertou.word + ''"]'');

                    if (chip) {
                        chip.classList.add(''encontrada'');
                    }

                    if (status) {
                        status.textContent = encontradas + ''/'' + total + '' encontradas'' + (encontradas === total ? '' - tudo achado! 🎉'' : '''');
                    }
                }

                inicio = null;
            });
        });
    });
})();
</script>',
     NULL, 'publicado', NOW()),
    ('jogo', 'kadosys', 'Caça-Nomes: Heróis do Velho Testamento', 'Ache o nome de grandes heróis da fé escondidos na grade de letras.', 'Velho Testamento', 'Heróis da Fé', NULL, NULL, 'medio', 6, 12, 10, 15, 8,
     '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">E</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="NOÉ">NOÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="MOISÉS">MOISÉS</span>
<span class="kids-cp-palavra" data-cp-palavra="JOSUÉ">JOSUÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="DAVI">DAVI</span>
<span class="kids-cp-palavra" data-cp-palavra="ESTER">ESTER</span>
<span class="kids-cp-palavra" data-cp-palavra="DANIEL">DANIEL</span>
<span class="kids-cp-palavra" data-cp-palavra="SANSÃO">SANSÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="GIDEÃO">GIDEÃO</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"MOISÉS","cells":[[7,7],[6,6],[5,5],[4,4],[3,3],[2,2]],"achada":false},{"word":"DANIEL","cells":[[7,6],[7,5],[7,4],[7,3],[7,2],[7,1]],"achada":false},{"word":"SANSÃO","cells":[[0,9],[1,9],[2,9],[3,9],[4,9],[5,9]],"achada":false},{"word":"GIDEÃO","cells":[[5,8],[4,7],[3,6],[2,5],[1,4],[0,3]],"achada":false},{"word":"JOSUÉ","cells":[[6,0],[5,0],[4,0],[3,0],[2,0]],"achada":false},{"word":"ESTER","cells":[[7,2],[6,2],[5,2],[4,2],[3,2]],"achada":false},{"word":"DAVI","cells":[[9,4],[9,5],[9,6],[9,7]],"achada":false},{"word":"NOÉ","cells":[[0,8],[1,8],[2,8]],"achada":false}]</script>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-cacapalavras]'').forEach(function (jogo) {
        var dadosEl = jogo.querySelector(''[data-cp-dados]'');
        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector(''[data-cp-status]'');
        var total = palavras.length;
        var encontradas = 0;
        var inicio = null;

        function celulaEm(r, c) {
            return jogo.querySelector(''[data-r="'' + r + ''"][data-c="'' + c + ''"]'');
        }

        function limparSelecao() {
            jogo.querySelectorAll(''.selecionada'').forEach(function (el) {
                el.classList.remove(''selecionada'');
            });
        }

        function caminhoEntre(r1, c1, r2, c2) {
            var dr = r2 - r1;
            var dc = c2 - c1;
            var passos = Math.max(Math.abs(dr), Math.abs(dc));

            if (passos === 0) {
                return null;
            }

            if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) {
                return null;
            }

            var stepR = dr === 0 ? 0 : dr / Math.abs(dr);
            var stepC = dc === 0 ? 0 : dc / Math.abs(dc);
            var caminho = [];

            for (var i = 0; i <= passos; i++) {
                caminho.push([r1 + stepR * i, c1 + stepC * i]);
            }

            return caminho;
        }

        function caminhosIguais(a, b) {
            if (a.length !== b.length) {
                return false;
            }

            var direto = a.every(function (p, i) { return p[0] === b[i][0] && p[1] === b[i][1]; });
            var reverso = a.every(function (p, i) { return p[0] === b[b.length - 1 - i][0] && p[1] === b[b.length - 1 - i][1]; });

            return direto || reverso;
        }

        jogo.querySelectorAll(''[data-cp-celula]'').forEach(function (celula) {
            celula.addEventListener(''click'', function () {
                if (celula.classList.contains(''encontrada'')) {
                    return;
                }

                var r = parseInt(celula.getAttribute(''data-r''), 10);
                var c = parseInt(celula.getAttribute(''data-c''), 10);

                if (!inicio) {
                    limparSelecao();
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var caminho = caminhoEntre(inicio.r, inicio.c, r, c);
                limparSelecao();

                if (!caminho) {
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var acertou = null;

                palavras.forEach(function (p) {
                    if (!p.achada && caminhosIguais(caminho, p.cells)) {
                        acertou = p;
                    }
                });

                if (acertou) {
                    acertou.achada = true;
                    encontradas++;

                    caminho.forEach(function (pos) {
                        var el = celulaEm(pos[0], pos[1]);

                        if (el) {
                            el.classList.add(''encontrada'');
                        }
                    });

                    var chip = jogo.querySelector(''[data-cp-palavra="'' + acertou.word + ''"]'');

                    if (chip) {
                        chip.classList.add(''encontrada'');
                    }

                    if (status) {
                        status.textContent = encontradas + ''/'' + total + '' encontradas'' + (encontradas === total ? '' - tudo achado! 🎉'' : '''');
                    }
                }

                inicio = null;
            });
        });
    });
})();
</script>',
     NULL, 'publicado', NOW()),
    ('jogo', 'kadosys', 'Caça-Nomes: Frutos do Espírito', 'Ache os frutos do Espírito escondidos na grade de letras.', 'Novo Testamento', 'Frutos do Espírito', 'Gálatas', NULL, 'dificil', 7, 12, 12, 15, 8,
     '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(11, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="10">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="10">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">Z</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="10">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="10">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="10">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="10">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">Ê</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="10">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">Í</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="10">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="10">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="10">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="3">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="4">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="6">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="9">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="10">D</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="AMOR">AMOR</span>
<span class="kids-cp-palavra" data-cp-palavra="ALEGRIA">ALEGRIA</span>
<span class="kids-cp-palavra" data-cp-palavra="PAZ">PAZ</span>
<span class="kids-cp-palavra" data-cp-palavra="PACIÊNCIA">PACIÊNCIA</span>
<span class="kids-cp-palavra" data-cp-palavra="BONDADE">BONDADE</span>
<span class="kids-cp-palavra" data-cp-palavra="FIDELIDADE">FIDELIDADE</span>
<span class="kids-cp-palavra" data-cp-palavra="MANSIDÃO">MANSIDÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="DOMÍNIO">DOMÍNIO</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"FIDELIDADE","cells":[[0,9],[1,9],[2,9],[3,9],[4,9],[5,9],[6,9],[7,9],[8,9],[9,9]],"achada":false},{"word":"PACIÊNCIA","cells":[[2,0],[3,1],[4,2],[5,3],[6,4],[7,5],[8,6],[9,7],[10,8]],"achada":false},{"word":"MANSIDÃO","cells":[[2,1],[3,2],[4,3],[5,4],[6,5],[7,6],[8,7],[9,8]],"achada":false},{"word":"ALEGRIA","cells":[[8,10],[7,10],[6,10],[5,10],[4,10],[3,10],[2,10]],"achada":false},{"word":"BONDADE","cells":[[7,7],[6,6],[5,5],[4,4],[3,3],[2,2],[1,1]],"achada":false},{"word":"DOMÍNIO","cells":[[4,1],[5,1],[6,1],[7,1],[8,1],[9,1],[10,1]],"achada":false},{"word":"AMOR","cells":[[4,5],[3,4],[2,3],[1,2]],"achada":false},{"word":"PAZ","cells":[[2,7],[2,6],[2,5]],"achada":false}]</script>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-cacapalavras]'').forEach(function (jogo) {
        var dadosEl = jogo.querySelector(''[data-cp-dados]'');
        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector(''[data-cp-status]'');
        var total = palavras.length;
        var encontradas = 0;
        var inicio = null;

        function celulaEm(r, c) {
            return jogo.querySelector(''[data-r="'' + r + ''"][data-c="'' + c + ''"]'');
        }

        function limparSelecao() {
            jogo.querySelectorAll(''.selecionada'').forEach(function (el) {
                el.classList.remove(''selecionada'');
            });
        }

        function caminhoEntre(r1, c1, r2, c2) {
            var dr = r2 - r1;
            var dc = c2 - c1;
            var passos = Math.max(Math.abs(dr), Math.abs(dc));

            if (passos === 0) {
                return null;
            }

            if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) {
                return null;
            }

            var stepR = dr === 0 ? 0 : dr / Math.abs(dr);
            var stepC = dc === 0 ? 0 : dc / Math.abs(dc);
            var caminho = [];

            for (var i = 0; i <= passos; i++) {
                caminho.push([r1 + stepR * i, c1 + stepC * i]);
            }

            return caminho;
        }

        function caminhosIguais(a, b) {
            if (a.length !== b.length) {
                return false;
            }

            var direto = a.every(function (p, i) { return p[0] === b[i][0] && p[1] === b[i][1]; });
            var reverso = a.every(function (p, i) { return p[0] === b[b.length - 1 - i][0] && p[1] === b[b.length - 1 - i][1]; });

            return direto || reverso;
        }

        jogo.querySelectorAll(''[data-cp-celula]'').forEach(function (celula) {
            celula.addEventListener(''click'', function () {
                if (celula.classList.contains(''encontrada'')) {
                    return;
                }

                var r = parseInt(celula.getAttribute(''data-r''), 10);
                var c = parseInt(celula.getAttribute(''data-c''), 10);

                if (!inicio) {
                    limparSelecao();
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var caminho = caminhoEntre(inicio.r, inicio.c, r, c);
                limparSelecao();

                if (!caminho) {
                    inicio = { r: r, c: c };
                    celula.classList.add(''selecionada'');

                    return;
                }

                var acertou = null;

                palavras.forEach(function (p) {
                    if (!p.achada && caminhosIguais(caminho, p.cells)) {
                        acertou = p;
                    }
                });

                if (acertou) {
                    acertou.achada = true;
                    encontradas++;

                    caminho.forEach(function (pos) {
                        var el = celulaEm(pos[0], pos[1]);

                        if (el) {
                            el.classList.add(''encontrada'');
                        }
                    });

                    var chip = jogo.querySelector(''[data-cp-palavra="'' + acertou.word + ''"]'');

                    if (chip) {
                        chip.classList.add(''encontrada'');
                    }

                    if (status) {
                        status.textContent = encontradas + ''/'' + total + '' encontradas'' + (encontradas === total ? '' - tudo achado! 🎉'' : '''');
                    }
                }

                inicio = null;
            });
        });
    });
})();
</script>',
     NULL, 'publicado', NOW());


-- Migration 060 (quiz + explicacao biblica) - ja incluida aqui pra
-- instalacoes novas partirem com explicacao em cada pergunta.
-- Gerado por gerar_migration_060.php - nao editar a mao.
UPDATE kids_conteudos SET quiz_perguntas = '[{"pergunta":"Quem construiu a arca?","alternativas":["Moisés","Noé","Davi","Abraão"],"correta":1,"explicacao":"Foi Noé! Deus pediu que ele construísse uma arca bem grande pra salvar sua família e os animais da grande enchente. Noé obedeceu, mesmo sem entender tudo - e Deus cumpriu a promessa de proteger quem confia nele."},{"pergunta":"Quantos dias e noites choveu no dilúvio?","alternativas":["7","40","100","3"],"correta":1,"explicacao":"Choveu 40 dias e 40 noites! Mesmo com tanta chuva, Noé e sua família ficaram seguros dentro da arca, porque confiaram no plano de Deus."},{"pergunta":"Quem derrotou o gigante Golias?","alternativas":["Saul","Salomão","Davi","Sansão"],"correta":2,"explicacao":"Foi Davi, ainda um jovem pastor! Com uma pedrinha, uma funda e muita fé em Deus, ele venceu um gigante guerreiro. Deus pode usar quem parece pequeno pra fazer coisas grandes."}]'
WHERE origem = 'kadosys' AND tipo = 'quiz' AND titulo = 'Quiz: Personagens da Bíblia';

UPDATE kids_conteudos SET quiz_perguntas = '[{"pergunta":"Qual é o primeiro livro da Bíblia?","alternativas":["Êxodo","Gênesis","Salmos","Mateus"],"correta":1,"explicacao":"É Gênesis! Esse livro conta o começo de tudo: a criação do mundo, do primeiro homem e da primeira mulher, feitos por Deus com muito amor."},{"pergunta":"Quantos livros tem o Novo Testamento?","alternativas":["27","39","66","12"],"correta":0,"explicacao":"São 27 livros! Eles contam a vida de Jesus, o começo da igreja e cartas cheias de ensinamentos pra quem quer viver seguindo a Deus."},{"pergunta":"Qual desses é um dos quatro Evangelhos?","alternativas":["Gênesis","Salmos","João","Rute"],"correta":2,"explicacao":"É o livro de João! Mateus, Marcos, Lucas e João são os quatro Evangelhos - cada um conta a história de Jesus com o seu próprio jeito."}]'
WHERE origem = 'kadosys' AND tipo = 'quiz' AND titulo = 'Quiz: Livros da Bíblia';

UPDATE kids_conteudos SET quiz_perguntas = '[{"pergunta":"O que Jesus transformou água em, nas bodas de Caná?","alternativas":["Suco","Vinho","Leite","Mel"],"correta":1,"explicacao":"Jesus transformou água em vinho! Foi o primeiro milagre dele, feito numa festa de casamento, mostrando que ele se importa até com os detalhes da nossa alegria."},{"pergunta":"Quantos pães Jesus usou para alimentar a multidão?","alternativas":["2","5","10","20"],"correta":1,"explicacao":"Foram só 5 pães (e 2 peixinhos)! Jesus multiplicou essa pequena oferta de um menino e alimentou milhares de pessoas. Com Deus, o pouco pode virar muito."},{"pergunta":"Sobre o que Jesus andou, para mostrar seu poder?","alternativas":["Fogo","Nuvens","Água","Areia"],"correta":2,"explicacao":"Jesus andou sobre a água do mar! Isso mostrou aos discípulos que ele tem poder sobre a natureza - e que podemos confiar nele mesmo nas tempestades da vida."}]'
WHERE origem = 'kadosys' AND tipo = 'quiz' AND titulo = 'Quiz: Milagres de Jesus';

UPDATE kids_conteudos SET quiz_perguntas = '[{"pergunta":"Quem construiu uma arca para salvar sua família e os animais?","alternativas":["Noé","Abraão","Moisés","Davi"],"correta":0,"explicacao":"Foi Noé! Ele confiou em Deus e obedeceu, mesmo quando ninguém mais acreditava que ia chover. Sua obediência salvou sua família."},{"pergunta":"Quem enfrentou o gigante Golias com uma funda?","alternativas":["Sansão","Davi","Josué","Gideão"],"correta":1,"explicacao":"Foi Davi! Ele não confiava só na sua força, mas no poder de Deus - por isso não teve medo de enfrentar um gigante."},{"pergunta":"Quem foi jogado na cova dos leões e não se machucou?","alternativas":["Daniel","José","Jonas","Elias"],"correta":0,"explicacao":"Foi Daniel! Ele continuou orando a Deus mesmo sendo proibido, e Deus fechou a boca dos leões para protegê-lo. Deus cuida de quem é fiel a ele."},{"pergunta":"Quem liderou o povo de Israel para fora do Egito?","alternativas":["Josué","Moisés","Abraão","Davi"],"correta":1,"explicacao":"Foi Moisés! Deus o escolheu para libertar o povo que vivia escravo no Egito e guiá-lo até a terra prometida."}]'
WHERE origem = 'kadosys' AND tipo = 'quiz' AND titulo = 'Quiz: Heróis do Velho Testamento';

UPDATE kids_conteudos SET quiz_perguntas = '[{"pergunta":"Na parábola do Bom Samaritano, quem ajudou o homem ferido?","alternativas":["Um sacerdote","Um samaritano","Um levita","Um soldado"],"correta":1,"explicacao":"Foi o samaritano! Mesmo sendo de um povo mal visto na época, ele parou para cuidar de um estranho ferido. Jesus ensinou que amar o próximo é cuidar de quem precisa, não importa quem seja."},{"pergunta":"Na parábola do filho pródigo, o que o pai fez quando o filho voltou?","alternativas":["Ficou bravo","Não deixou entrar","Correu para abraçá-lo","Mandou ele embora"],"correta":2,"explicacao":"O pai correu para abraçá-lo com alegria! Essa história mostra como Deus nos recebe de braços abertos sempre que voltamos para ele, não importa o que tenha acontecido."},{"pergunta":"Na parábola da ovelha perdida, quantas ovelhas o pastor tinha ao todo?","alternativas":["10","50","100","1000"],"correta":2,"explicacao":"Eram 100 ovelhas! O pastor deixou as outras 99 para ir buscar só uma que se perdeu, mostrando que cada pessoa é muito importante para Deus."},{"pergunta":"O que a semente de mostarda representa na parábola de Jesus?","alternativas":["O Reino de Deus crescendo","Uma árvore grande","Um pássaro","Uma flor"],"correta":0,"explicacao":"Representa o Reino de Deus! Ele começa pequeno, como uma sementinha, mas cresce cada vez mais - assim como a fé pode crescer dentro de nós."}]'
WHERE origem = 'kadosys' AND tipo = 'quiz' AND titulo = 'Quiz: Parábolas de Jesus';

UPDATE kids_conteudos SET quiz_perguntas = '[{"pergunta":"Qual discípulo caminhou sobre a água com Jesus?","alternativas":["João","Tiago","Pedro","André"],"correta":2,"explicacao":"Foi Pedro! Ele deu alguns passos sobre a água olhando para Jesus, mas começou a afundar quando teve medo. Jesus o segurou - ele sempre estende a mão pra nós."},{"pergunta":"Qual discípulo traiu Jesus?","alternativas":["Tomé","Judas","Filipe","Mateus"],"correta":1,"explicacao":"Foi Judas Iscariotes. Ele entregou Jesus por dinheiro, uma escolha muito triste. A história de Judas nos lembra como é importante ser fiel de verdade."},{"pergunta":"Qual discípulo duvidou da ressurreição até ver Jesus com seus próprios olhos?","alternativas":["Tomé","Pedro","João","Bartolomeu"],"correta":0,"explicacao":"Foi Tomé! Depois de ver e tocar em Jesus ressuscitado, ele finalmente creu. Jesus disse que é ainda mais especial crer mesmo sem ver."},{"pergunta":"Qual era a profissão de Mateus antes de seguir Jesus?","alternativas":["Pescador","Cobrador de impostos","Médico","Carpinteiro"],"correta":1,"explicacao":"Mateus cobrava impostos, um trabalho que muita gente não gostava. Mesmo assim, Jesus o chamou para ser discípulo - Deus escolhe qualquer pessoa que queira segui-lo."}]'
WHERE origem = 'kadosys' AND tipo = 'quiz' AND titulo = 'Quiz: Os 12 Discípulos';

UPDATE kids_conteudos SET quiz_perguntas = '[{"pergunta":"Em que dia Deus criou o sol, a lua e as estrelas?","alternativas":["Dia 2","Dia 3","Dia 4","Dia 5"],"correta":2,"explicacao":"Foi no quarto dia! Deus organizou tudo com cuidado e ordem, um passo de cada vez, para que o mundo funcionasse direitinho."},{"pergunta":"Em que dia Deus descansou?","alternativas":["Dia 5","Dia 6","Dia 7","Dia 1"],"correta":2,"explicacao":"No sétimo dia! Deus descansou depois de criar tudo, nos ensinando que descansar também é importante em nossa semana."},{"pergunta":"O que Deus criou primeiro?","alternativas":["Os animais","A luz","O homem","As plantas"],"correta":1,"explicacao":"Foi a luz! No primeiro dia, Deus disse \\"haja luz\\" e a escuridão deu lugar à claridade - o começo de tudo o que ele ainda ia criar."},{"pergunta":"De que Deus formou o primeiro homem?","alternativas":["Água","Pó da terra","Fogo","Madeira"],"correta":1,"explicacao":"Do pó da terra! Deus o formou com as próprias mãos e soprou nele o fôlego de vida - cada pessoa é uma criação especial e única de Deus."}]'
WHERE origem = 'kadosys' AND tipo = 'quiz' AND titulo = 'Quiz: A Criação do Mundo';

UPDATE kids_conteudos SET quiz_perguntas = '[{"pergunta":"Quantos frutos do Espírito são citados em Gálatas 5?","alternativas":["5","7","9","12"],"correta":2,"explicacao":"São 9 frutos: amor, alegria, paz, paciência, benignidade, bondade, fidelidade, mansidão e domínio próprio. Eles crescem em nós quando vivemos perto de Deus."},{"pergunta":"Qual destes é um fruto do Espírito?","alternativas":["Inveja","Paciência","Orgulho","Preguiça"],"correta":1,"explicacao":"É a paciência! Ela nos ajuda a esperar e a lidar bem com as dificuldades do dia a dia, confiando que Deus está no controle."},{"pergunta":"Em qual livro da Bíblia encontramos a lista do Fruto do Espírito?","alternativas":["Romanos","Gálatas","Salmos","Atos"],"correta":1,"explicacao":"É em Gálatas, capítulo 5! O apóstolo Paulo escreveu essa carta para ensinar como viver guiado pelo Espírito Santo."},{"pergunta":"Além do amor e da alegria, qual outro fruto começa com a letra P?","alternativas":["Fé","Paz","Bondade","Mansidão"],"correta":1,"explicacao":"É a paz! Deus quer que tenhamos um coração tranquilo, sem brigas nem preocupação demais, confiando nele."}]'
WHERE origem = 'kadosys' AND tipo = 'quiz' AND titulo = 'Quiz: Frutos do Espírito';

UPDATE kids_conteudos SET quiz_perguntas = '[{"pergunta":"O que aconteceu no dia de Pentecostes?","alternativas":["Choveu","O Espírito Santo desceu sobre os discípulos","Houve um terremoto","Jesus subiu ao céu"],"correta":1,"explicacao":"O Espírito Santo desceu sobre os discípulos! Isso deu coragem e força para eles contarem a todos as boas notícias sobre Jesus, e assim a igreja começou."},{"pergunta":"Quem era Saulo antes de se tornar o apóstolo Paulo?","alternativas":["Um pescador","Um perseguidor dos cristãos","Um rei","Um sacerdote"],"correta":1,"explicacao":"Ele perseguia os cristãos! Mas depois de encontrar Jesus numa luz forte no caminho, Saulo mudou completamente e virou Paulo, um dos maiores anunciadores da fé."},{"pergunta":"O que os primeiros cristãos faziam juntos, segundo Atos 2?","alternativas":["Brigavam por comida","Partilhavam tudo o que tinham","Escondiam suas coisas","Viviam sozinhos"],"correta":1,"explicacao":"Eles partilhavam tudo o que tinham! Cuidavam uns dos outros com generosidade, um exemplo bonito de amor entre irmãos na fé."},{"pergunta":"Quem foi o primeiro mártir cristão, apedrejado por sua fé?","alternativas":["Pedro","Estêvão","Filipe","Tiago"],"correta":1,"explicacao":"Foi Estêvão! Mesmo sendo apedrejado, ele perdoou quem fez aquilo com ele, seguindo o exemplo de amor e perdão que Jesus ensinou."}]'
WHERE origem = 'kadosys' AND tipo = 'quiz' AND titulo = 'Quiz: A Igreja Primitiva';

UPDATE kids_conteudos SET quiz_perguntas = '[{"pergunta":"Segundo Provérbios, o que é o começo da sabedoria?","alternativas":["Estudar muito","O temor do Senhor","Ser rico","Ter muitos amigos"],"correta":1,"explicacao":"É o temor do Senhor - ou seja, respeitar e amar a Deus de verdade. Quando colocamos Deus em primeiro lugar, aprendemos a viver com sabedoria."},{"pergunta":"Provérbios ensina que devemos confiar no Senhor de...","alternativas":["Todo o coração","Vez em quando","Apenas nos domingos","Só quando precisamos"],"correta":0,"explicacao":"De todo o coração! Provérbios 3:5 ensina para confiarmos em Deus sempre, sem depender só do nosso próprio entendimento."},{"pergunta":"Quem escreveu a maior parte do livro de Provérbios?","alternativas":["Davi","Salomão","Moisés","Paulo"],"correta":1,"explicacao":"Foi o rei Salomão! Deus deu a ele uma sabedoria muito grande, e Salomão escreveu esses conselhos para ajudar as pessoas a viverem melhor."},{"pergunta":"O que Provérbios diz sobre a resposta mansa?","alternativas":["Que afasta o furor","Que não serve pra nada","Que é sinal de fraqueza","Que ninguém entende"],"correta":0,"explicacao":"Que ela afasta o furor! Responder com calma, mesmo quando alguém está bravo, ajuda a acalmar a situação em vez de piorar a briga."}]'
WHERE origem = 'kadosys' AND tipo = 'quiz' AND titulo = 'Quiz: Provérbios e Sabedoria';

UPDATE kids_conteudos SET quiz_perguntas = '[{"pergunta":"Em que cidade Jesus nasceu?","alternativas":["Nazaré","Belém","Jerusalém","Jericó"],"correta":1,"explicacao":"Foi em Belém! Isso já tinha sido anunciado por profetas centenas de anos antes - Deus cumpre suas promessas no tempo certo."},{"pergunta":"O que os pastores viram no céu anunciando o nascimento de Jesus?","alternativas":["Um cometa","Uma tempestade","Anjos","Um arco-íris"],"correta":2,"explicacao":"Eles viram anjos! Os anjos anunciaram a boa notícia do nascimento de Jesus primeiro para pastores simples, mostrando que essa alegria é para todos."},{"pergunta":"O que comemoramos na Páscoa cristã?","alternativas":["O nascimento de Jesus","A ressurreição de Jesus","O batismo de Jesus","A subida ao céu"],"correta":1,"explicacao":"A ressurreição de Jesus! Ele venceu a morte e está vivo, e essa é a maior alegria da fé cristã."},{"pergunta":"Quantos dias depois de morrer Jesus ressuscitou?","alternativas":["1","2","3","7"],"correta":2,"explicacao":"No terceiro dia! Assim como Jesus prometeu antes de morrer, ele ressuscitou exatamente como havia dito - podemos confiar em cada palavra dele."}]'
WHERE origem = 'kadosys' AND tipo = 'quiz' AND titulo = 'Quiz: Natal e Páscoa';

-- Migration 061 (caca-palavras: selecao por arraste) - ja incluida
-- aqui pra instalacoes novas partirem com a interacao corrigida.
-- Gerado por gerar_migration_061.php - nao editar a mao.
UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">N</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="PEDRO">PEDRO</span>
<span class="kids-cp-palavra" data-cp-palavra="ANDRÉ">ANDRÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="TIAGO">TIAGO</span>
<span class="kids-cp-palavra" data-cp-palavra="JOÃO">JOÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="FILIPE">FILIPE</span>
<span class="kids-cp-palavra" data-cp-palavra="TOMÉ">TOMÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="MATEUS">MATEUS</span>
<span class="kids-cp-palavra" data-cp-palavra="JUDAS">JUDAS</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"FILIPE","cells":[[5,3],[4,3],[3,3],[2,3],[1,3],[0,3]],"achada":false},{"word":"MATEUS","cells":[[8,1],[8,2],[8,3],[8,4],[8,5],[8,6]],"achada":false},{"word":"PEDRO","cells":[[1,4],[2,4],[3,4],[4,4],[5,4]],"achada":false},{"word":"ANDRÉ","cells":[[3,8],[4,7],[5,6],[6,5],[7,4]],"achada":false},{"word":"TIAGO","cells":[[9,4],[9,5],[9,6],[9,7],[9,8]],"achada":false},{"word":"JUDAS","cells":[[4,0],[3,0],[2,0],[1,0],[0,0]],"achada":false},{"word":"JOÃO","cells":[[1,2],[2,2],[3,2],[4,2]],"achada":false},{"word":"TOMÉ","cells":[[2,8],[3,7],[4,6],[5,5]],"achada":false}]</script>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-cacapalavras]'').forEach(function (jogo) {
        var dadosEl = jogo.querySelector(''[data-cp-dados]'');
        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector(''[data-cp-status]'');
        var total = palavras.length;
        var encontradas = 0;

        var ancora = null;
        var caminhoAtual = null;
        var ativo = false;
        var moveu = false;

        function celulaEm(r, c) {
            return jogo.querySelector(''[data-r="'' + r + ''"][data-c="'' + c + ''"]'');
        }

        function limparSelecao() {
            jogo.querySelectorAll(''.selecionada'').forEach(function (el) {
                el.classList.remove(''selecionada'');
            });
        }

        function marcarCaminho(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''selecionada'');
                }
            });
        }

        function caminhoEntre(r1, c1, r2, c2) {
            var dr = r2 - r1;
            var dc = c2 - c1;
            var passos = Math.max(Math.abs(dr), Math.abs(dc));

            if (passos === 0) {
                return [[r1, c1]];
            }

            if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) {
                return null;
            }

            var stepR = dr === 0 ? 0 : dr / Math.abs(dr);
            var stepC = dc === 0 ? 0 : dc / Math.abs(dc);
            var caminho = [];

            for (var i = 0; i <= passos; i++) {
                caminho.push([r1 + stepR * i, c1 + stepC * i]);
            }

            return caminho;
        }

        function caminhosIguais(a, b) {
            if (a.length !== b.length) {
                return false;
            }

            var direto = a.every(function (p, i) { return p[0] === b[i][0] && p[1] === b[i][1]; });
            var reverso = a.every(function (p, i) { return p[0] === b[b.length - 1 - i][0] && p[1] === b[b.length - 1 - i][1]; });

            return direto || reverso;
        }

        function achaPalavra(caminho) {
            var encontrada = null;

            palavras.forEach(function (p) {
                if (!p.achada && caminhosIguais(caminho, p.cells)) {
                    encontrada = p;
                }
            });

            return encontrada;
        }

        function marcarEncontrada(palavra, caminho) {
            palavra.achada = true;
            encontradas++;

            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''encontrada'');
                }
            });

            var chip = jogo.querySelector(''[data-cp-palavra="'' + palavra.word + ''"]'');

            if (chip) {
                chip.classList.add(''encontrada'');
            }

            if (status) {
                status.textContent = encontradas + ''/'' + total + '' encontradas'' + (encontradas === total ? '' - tudo achado! 🎉'' : '''');
            }
        }

        function flashErro(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''errada-tmp'');
                }
            });

            setTimeout(function () {
                caminho.forEach(function (pos) {
                    var el = celulaEm(pos[0], pos[1]);

                    if (el) {
                        el.classList.remove(''errada-tmp'');
                    }
                });
            }, 350);
        }

        function iniciarSelecao(r, c) {
            ancora = { r: r, c: c };
            caminhoAtual = [[r, c]];
            limparSelecao();
            marcarCaminho(caminhoAtual);
        }

        function atualizarCaminho(r, c) {
            var caminho = caminhoEntre(ancora.r, ancora.c, r, c);

            if (caminho) {
                caminhoAtual = caminho;
                limparSelecao();
                marcarCaminho(caminhoAtual);
            }
        }

        function cancelarSelecao() {
            ancora = null;
            caminhoAtual = null;
            limparSelecao();
        }

        function finalizarSelecao() {
            if (!ancora || !caminhoAtual || caminhoAtual.length < 2) {
                return;
            }

            var acertou = achaPalavra(caminhoAtual);

            if (acertou) {
                marcarEncontrada(acertou, caminhoAtual);
            } else {
                flashErro(caminhoAtual);
            }

            limparSelecao();
            ancora = null;
            caminhoAtual = null;
        }

        jogo.querySelectorAll(''[data-cp-celula]'').forEach(function (celula) {
            celula.addEventListener(''pointerdown'', function (event) {
                if (celula.classList.contains(''encontrada'')) {
                    return;
                }

                event.preventDefault();

                var r = parseInt(celula.getAttribute(''data-r''), 10);
                var c = parseInt(celula.getAttribute(''data-c''), 10);

                ativo = true;
                moveu = false;

                if (!ancora) {
                    iniciarSelecao(r, c);

                    return;
                }

                if (r === ancora.r && c === ancora.c) {
                    cancelarSelecao();

                    return;
                }

                atualizarCaminho(r, c);
                finalizarSelecao();
            });
        });

        jogo.addEventListener(''pointermove'', function (event) {
            if (!ativo || !ancora) {
                return;
            }

            var alvo = document.elementFromPoint(event.clientX, event.clientY);
            var celula = alvo ? alvo.closest(''[data-cp-celula]'') : null;

            if (!celula || !jogo.contains(celula) || celula.classList.contains(''encontrada'')) {
                return;
            }

            moveu = true;

            var r = parseInt(celula.getAttribute(''data-r''), 10);
            var c = parseInt(celula.getAttribute(''data-c''), 10);

            atualizarCaminho(r, c);
        });

        document.addEventListener(''pointerup'', function () {
            if (!ativo) {
                return;
            }

            ativo = false;

            if (moveu) {
                finalizarSelecao();
            }
        });

        document.addEventListener(''pointercancel'', function () {
            ativo = false;
        });
    });
})();
</script>'
WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Caça-Nomes: Os 12 Discípulos';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">E</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="NOÉ">NOÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="MOISÉS">MOISÉS</span>
<span class="kids-cp-palavra" data-cp-palavra="JOSUÉ">JOSUÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="DAVI">DAVI</span>
<span class="kids-cp-palavra" data-cp-palavra="ESTER">ESTER</span>
<span class="kids-cp-palavra" data-cp-palavra="DANIEL">DANIEL</span>
<span class="kids-cp-palavra" data-cp-palavra="SANSÃO">SANSÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="GIDEÃO">GIDEÃO</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"MOISÉS","cells":[[7,7],[6,6],[5,5],[4,4],[3,3],[2,2]],"achada":false},{"word":"DANIEL","cells":[[7,6],[7,5],[7,4],[7,3],[7,2],[7,1]],"achada":false},{"word":"SANSÃO","cells":[[0,9],[1,9],[2,9],[3,9],[4,9],[5,9]],"achada":false},{"word":"GIDEÃO","cells":[[5,8],[4,7],[3,6],[2,5],[1,4],[0,3]],"achada":false},{"word":"JOSUÉ","cells":[[6,0],[5,0],[4,0],[3,0],[2,0]],"achada":false},{"word":"ESTER","cells":[[7,2],[6,2],[5,2],[4,2],[3,2]],"achada":false},{"word":"DAVI","cells":[[9,4],[9,5],[9,6],[9,7]],"achada":false},{"word":"NOÉ","cells":[[0,8],[1,8],[2,8]],"achada":false}]</script>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-cacapalavras]'').forEach(function (jogo) {
        var dadosEl = jogo.querySelector(''[data-cp-dados]'');
        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector(''[data-cp-status]'');
        var total = palavras.length;
        var encontradas = 0;

        var ancora = null;
        var caminhoAtual = null;
        var ativo = false;
        var moveu = false;

        function celulaEm(r, c) {
            return jogo.querySelector(''[data-r="'' + r + ''"][data-c="'' + c + ''"]'');
        }

        function limparSelecao() {
            jogo.querySelectorAll(''.selecionada'').forEach(function (el) {
                el.classList.remove(''selecionada'');
            });
        }

        function marcarCaminho(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''selecionada'');
                }
            });
        }

        function caminhoEntre(r1, c1, r2, c2) {
            var dr = r2 - r1;
            var dc = c2 - c1;
            var passos = Math.max(Math.abs(dr), Math.abs(dc));

            if (passos === 0) {
                return [[r1, c1]];
            }

            if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) {
                return null;
            }

            var stepR = dr === 0 ? 0 : dr / Math.abs(dr);
            var stepC = dc === 0 ? 0 : dc / Math.abs(dc);
            var caminho = [];

            for (var i = 0; i <= passos; i++) {
                caminho.push([r1 + stepR * i, c1 + stepC * i]);
            }

            return caminho;
        }

        function caminhosIguais(a, b) {
            if (a.length !== b.length) {
                return false;
            }

            var direto = a.every(function (p, i) { return p[0] === b[i][0] && p[1] === b[i][1]; });
            var reverso = a.every(function (p, i) { return p[0] === b[b.length - 1 - i][0] && p[1] === b[b.length - 1 - i][1]; });

            return direto || reverso;
        }

        function achaPalavra(caminho) {
            var encontrada = null;

            palavras.forEach(function (p) {
                if (!p.achada && caminhosIguais(caminho, p.cells)) {
                    encontrada = p;
                }
            });

            return encontrada;
        }

        function marcarEncontrada(palavra, caminho) {
            palavra.achada = true;
            encontradas++;

            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''encontrada'');
                }
            });

            var chip = jogo.querySelector(''[data-cp-palavra="'' + palavra.word + ''"]'');

            if (chip) {
                chip.classList.add(''encontrada'');
            }

            if (status) {
                status.textContent = encontradas + ''/'' + total + '' encontradas'' + (encontradas === total ? '' - tudo achado! 🎉'' : '''');
            }
        }

        function flashErro(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''errada-tmp'');
                }
            });

            setTimeout(function () {
                caminho.forEach(function (pos) {
                    var el = celulaEm(pos[0], pos[1]);

                    if (el) {
                        el.classList.remove(''errada-tmp'');
                    }
                });
            }, 350);
        }

        function iniciarSelecao(r, c) {
            ancora = { r: r, c: c };
            caminhoAtual = [[r, c]];
            limparSelecao();
            marcarCaminho(caminhoAtual);
        }

        function atualizarCaminho(r, c) {
            var caminho = caminhoEntre(ancora.r, ancora.c, r, c);

            if (caminho) {
                caminhoAtual = caminho;
                limparSelecao();
                marcarCaminho(caminhoAtual);
            }
        }

        function cancelarSelecao() {
            ancora = null;
            caminhoAtual = null;
            limparSelecao();
        }

        function finalizarSelecao() {
            if (!ancora || !caminhoAtual || caminhoAtual.length < 2) {
                return;
            }

            var acertou = achaPalavra(caminhoAtual);

            if (acertou) {
                marcarEncontrada(acertou, caminhoAtual);
            } else {
                flashErro(caminhoAtual);
            }

            limparSelecao();
            ancora = null;
            caminhoAtual = null;
        }

        jogo.querySelectorAll(''[data-cp-celula]'').forEach(function (celula) {
            celula.addEventListener(''pointerdown'', function (event) {
                if (celula.classList.contains(''encontrada'')) {
                    return;
                }

                event.preventDefault();

                var r = parseInt(celula.getAttribute(''data-r''), 10);
                var c = parseInt(celula.getAttribute(''data-c''), 10);

                ativo = true;
                moveu = false;

                if (!ancora) {
                    iniciarSelecao(r, c);

                    return;
                }

                if (r === ancora.r && c === ancora.c) {
                    cancelarSelecao();

                    return;
                }

                atualizarCaminho(r, c);
                finalizarSelecao();
            });
        });

        jogo.addEventListener(''pointermove'', function (event) {
            if (!ativo || !ancora) {
                return;
            }

            var alvo = document.elementFromPoint(event.clientX, event.clientY);
            var celula = alvo ? alvo.closest(''[data-cp-celula]'') : null;

            if (!celula || !jogo.contains(celula) || celula.classList.contains(''encontrada'')) {
                return;
            }

            moveu = true;

            var r = parseInt(celula.getAttribute(''data-r''), 10);
            var c = parseInt(celula.getAttribute(''data-c''), 10);

            atualizarCaminho(r, c);
        });

        document.addEventListener(''pointerup'', function () {
            if (!ativo) {
                return;
            }

            ativo = false;

            if (moveu) {
                finalizarSelecao();
            }
        });

        document.addEventListener(''pointercancel'', function () {
            ativo = false;
        });
    });
})();
</script>'
WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Caça-Nomes: Heróis do Velho Testamento';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(11, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="10">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="10">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">Z</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="10">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="10">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="10">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="10">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">Ê</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="10">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">Í</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="10">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="10">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="10">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="3">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="4">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="6">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="9">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="10">D</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="AMOR">AMOR</span>
<span class="kids-cp-palavra" data-cp-palavra="ALEGRIA">ALEGRIA</span>
<span class="kids-cp-palavra" data-cp-palavra="PAZ">PAZ</span>
<span class="kids-cp-palavra" data-cp-palavra="PACIÊNCIA">PACIÊNCIA</span>
<span class="kids-cp-palavra" data-cp-palavra="BONDADE">BONDADE</span>
<span class="kids-cp-palavra" data-cp-palavra="FIDELIDADE">FIDELIDADE</span>
<span class="kids-cp-palavra" data-cp-palavra="MANSIDÃO">MANSIDÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="DOMÍNIO">DOMÍNIO</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"FIDELIDADE","cells":[[0,9],[1,9],[2,9],[3,9],[4,9],[5,9],[6,9],[7,9],[8,9],[9,9]],"achada":false},{"word":"PACIÊNCIA","cells":[[2,0],[3,1],[4,2],[5,3],[6,4],[7,5],[8,6],[9,7],[10,8]],"achada":false},{"word":"MANSIDÃO","cells":[[2,1],[3,2],[4,3],[5,4],[6,5],[7,6],[8,7],[9,8]],"achada":false},{"word":"ALEGRIA","cells":[[8,10],[7,10],[6,10],[5,10],[4,10],[3,10],[2,10]],"achada":false},{"word":"BONDADE","cells":[[7,7],[6,6],[5,5],[4,4],[3,3],[2,2],[1,1]],"achada":false},{"word":"DOMÍNIO","cells":[[4,1],[5,1],[6,1],[7,1],[8,1],[9,1],[10,1]],"achada":false},{"word":"AMOR","cells":[[4,5],[3,4],[2,3],[1,2]],"achada":false},{"word":"PAZ","cells":[[2,7],[2,6],[2,5]],"achada":false}]</script>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-cacapalavras]'').forEach(function (jogo) {
        var dadosEl = jogo.querySelector(''[data-cp-dados]'');
        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector(''[data-cp-status]'');
        var total = palavras.length;
        var encontradas = 0;

        var ancora = null;
        var caminhoAtual = null;
        var ativo = false;
        var moveu = false;

        function celulaEm(r, c) {
            return jogo.querySelector(''[data-r="'' + r + ''"][data-c="'' + c + ''"]'');
        }

        function limparSelecao() {
            jogo.querySelectorAll(''.selecionada'').forEach(function (el) {
                el.classList.remove(''selecionada'');
            });
        }

        function marcarCaminho(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''selecionada'');
                }
            });
        }

        function caminhoEntre(r1, c1, r2, c2) {
            var dr = r2 - r1;
            var dc = c2 - c1;
            var passos = Math.max(Math.abs(dr), Math.abs(dc));

            if (passos === 0) {
                return [[r1, c1]];
            }

            if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) {
                return null;
            }

            var stepR = dr === 0 ? 0 : dr / Math.abs(dr);
            var stepC = dc === 0 ? 0 : dc / Math.abs(dc);
            var caminho = [];

            for (var i = 0; i <= passos; i++) {
                caminho.push([r1 + stepR * i, c1 + stepC * i]);
            }

            return caminho;
        }

        function caminhosIguais(a, b) {
            if (a.length !== b.length) {
                return false;
            }

            var direto = a.every(function (p, i) { return p[0] === b[i][0] && p[1] === b[i][1]; });
            var reverso = a.every(function (p, i) { return p[0] === b[b.length - 1 - i][0] && p[1] === b[b.length - 1 - i][1]; });

            return direto || reverso;
        }

        function achaPalavra(caminho) {
            var encontrada = null;

            palavras.forEach(function (p) {
                if (!p.achada && caminhosIguais(caminho, p.cells)) {
                    encontrada = p;
                }
            });

            return encontrada;
        }

        function marcarEncontrada(palavra, caminho) {
            palavra.achada = true;
            encontradas++;

            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''encontrada'');
                }
            });

            var chip = jogo.querySelector(''[data-cp-palavra="'' + palavra.word + ''"]'');

            if (chip) {
                chip.classList.add(''encontrada'');
            }

            if (status) {
                status.textContent = encontradas + ''/'' + total + '' encontradas'' + (encontradas === total ? '' - tudo achado! 🎉'' : '''');
            }
        }

        function flashErro(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''errada-tmp'');
                }
            });

            setTimeout(function () {
                caminho.forEach(function (pos) {
                    var el = celulaEm(pos[0], pos[1]);

                    if (el) {
                        el.classList.remove(''errada-tmp'');
                    }
                });
            }, 350);
        }

        function iniciarSelecao(r, c) {
            ancora = { r: r, c: c };
            caminhoAtual = [[r, c]];
            limparSelecao();
            marcarCaminho(caminhoAtual);
        }

        function atualizarCaminho(r, c) {
            var caminho = caminhoEntre(ancora.r, ancora.c, r, c);

            if (caminho) {
                caminhoAtual = caminho;
                limparSelecao();
                marcarCaminho(caminhoAtual);
            }
        }

        function cancelarSelecao() {
            ancora = null;
            caminhoAtual = null;
            limparSelecao();
        }

        function finalizarSelecao() {
            if (!ancora || !caminhoAtual || caminhoAtual.length < 2) {
                return;
            }

            var acertou = achaPalavra(caminhoAtual);

            if (acertou) {
                marcarEncontrada(acertou, caminhoAtual);
            } else {
                flashErro(caminhoAtual);
            }

            limparSelecao();
            ancora = null;
            caminhoAtual = null;
        }

        jogo.querySelectorAll(''[data-cp-celula]'').forEach(function (celula) {
            celula.addEventListener(''pointerdown'', function (event) {
                if (celula.classList.contains(''encontrada'')) {
                    return;
                }

                event.preventDefault();

                var r = parseInt(celula.getAttribute(''data-r''), 10);
                var c = parseInt(celula.getAttribute(''data-c''), 10);

                ativo = true;
                moveu = false;

                if (!ancora) {
                    iniciarSelecao(r, c);

                    return;
                }

                if (r === ancora.r && c === ancora.c) {
                    cancelarSelecao();

                    return;
                }

                atualizarCaminho(r, c);
                finalizarSelecao();
            });
        });

        jogo.addEventListener(''pointermove'', function (event) {
            if (!ativo || !ancora) {
                return;
            }

            var alvo = document.elementFromPoint(event.clientX, event.clientY);
            var celula = alvo ? alvo.closest(''[data-cp-celula]'') : null;

            if (!celula || !jogo.contains(celula) || celula.classList.contains(''encontrada'')) {
                return;
            }

            moveu = true;

            var r = parseInt(celula.getAttribute(''data-r''), 10);
            var c = parseInt(celula.getAttribute(''data-c''), 10);

            atualizarCaminho(r, c);
        });

        document.addEventListener(''pointerup'', function () {
            if (!ativo) {
                return;
            }

            ativo = false;

            if (moveu) {
                finalizarSelecao();
            }
        });

        document.addEventListener(''pointercancel'', function () {
            ativo = false;
        });
    });
})();
</script>'
WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Caça-Nomes: Frutos do Espírito';

-- Migration 062 (loja de moedas do avatar) - ja incluida aqui pra
-- instalacoes novas partirem com a tabela de compras existente.

CREATE TABLE kids_avatar_compras (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    crianca_id INT UNSIGNED NOT NULL,
    categoria ENUM('chapeu', 'acessorio', 'fundo', 'titulo') NOT NULL,
    slug VARCHAR(60) NOT NULL,
    custo_moedas INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kids_avatar_compras (crianca_id, categoria, slug),
    CONSTRAINT fk_kids_avatar_compras_crianca FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 063 (atividades interativas + loja de moedas) - ja
-- incluida aqui pra instalacoes novas partirem com os widgets
-- interativos (Complete o Versiculo, Ligue o Personagem, Desenhe
-- a sua Oracao) e o 4o caca-palavras desde o inicio.

DELETE FROM kids_conteudos WHERE tipo = 'atividade' AND origem = 'kadosys' AND titulo = 'Caça-palavras: Personagens do Novo Testamento';

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Caça-Nomes: Personagens do Novo Testamento', 'Ache os nomes de Pedro, João, Maria e outros personagens escondidos na grade de letras.', 'Novo Testamento', 'Personagens', NULL, NULL, 'facil', 6, 11, 10, 15, 8,
     '<div class="kids-cacapalavras" data-cacapalavras>
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
</div>
<script>
(function () {
    document.querySelectorAll(''[data-cacapalavras]'').forEach(function (jogo) {
        var dadosEl = jogo.querySelector(''[data-cp-dados]'');
        var palavras = JSON.parse(dadosEl.textContent);
        var status = jogo.querySelector(''[data-cp-status]'');
        var total = palavras.length;
        var encontradas = 0;

        var ancora = null;
        var caminhoAtual = null;
        var ativo = false;
        var moveu = false;

        function celulaEm(r, c) {
            return jogo.querySelector(''[data-r="'' + r + ''"][data-c="'' + c + ''"]'');
        }

        function limparSelecao() {
            jogo.querySelectorAll(''.selecionada'').forEach(function (el) {
                el.classList.remove(''selecionada'');
            });
        }

        function marcarCaminho(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''selecionada'');
                }
            });
        }

        function caminhoEntre(r1, c1, r2, c2) {
            var dr = r2 - r1;
            var dc = c2 - c1;
            var passos = Math.max(Math.abs(dr), Math.abs(dc));

            if (passos === 0) {
                return [[r1, c1]];
            }

            if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) {
                return null;
            }

            var stepR = dr === 0 ? 0 : dr / Math.abs(dr);
            var stepC = dc === 0 ? 0 : dc / Math.abs(dc);
            var caminho = [];

            for (var i = 0; i <= passos; i++) {
                caminho.push([r1 + stepR * i, c1 + stepC * i]);
            }

            return caminho;
        }

        function caminhosIguais(a, b) {
            if (a.length !== b.length) {
                return false;
            }

            var direto = a.every(function (p, i) { return p[0] === b[i][0] && p[1] === b[i][1]; });
            var reverso = a.every(function (p, i) { return p[0] === b[b.length - 1 - i][0] && p[1] === b[b.length - 1 - i][1]; });

            return direto || reverso;
        }

        function achaPalavra(caminho) {
            var encontrada = null;

            palavras.forEach(function (p) {
                if (!p.achada && caminhosIguais(caminho, p.cells)) {
                    encontrada = p;
                }
            });

            return encontrada;
        }

        function marcarEncontrada(palavra, caminho) {
            palavra.achada = true;
            encontradas++;

            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''encontrada'');
                }
            });

            var chip = jogo.querySelector(''[data-cp-palavra="'' + palavra.word + ''"]'');

            if (chip) {
                chip.classList.add(''encontrada'');
            }

            if (status) {
                status.textContent = encontradas + ''/'' + total + '' encontradas'' + (encontradas === total ? '' - tudo achado! 🎉'' : '''');
            }
        }

        function flashErro(caminho) {
            caminho.forEach(function (pos) {
                var el = celulaEm(pos[0], pos[1]);

                if (el) {
                    el.classList.add(''errada-tmp'');
                }
            });

            setTimeout(function () {
                caminho.forEach(function (pos) {
                    var el = celulaEm(pos[0], pos[1]);

                    if (el) {
                        el.classList.remove(''errada-tmp'');
                    }
                });
            }, 350);
        }

        function iniciarSelecao(r, c) {
            ancora = { r: r, c: c };
            caminhoAtual = [[r, c]];
            limparSelecao();
            marcarCaminho(caminhoAtual);
        }

        function atualizarCaminho(r, c) {
            var caminho = caminhoEntre(ancora.r, ancora.c, r, c);

            if (caminho) {
                caminhoAtual = caminho;
                limparSelecao();
                marcarCaminho(caminhoAtual);
            }
        }

        function cancelarSelecao() {
            ancora = null;
            caminhoAtual = null;
            limparSelecao();
        }

        function finalizarSelecao() {
            if (!ancora || !caminhoAtual || caminhoAtual.length < 2) {
                return;
            }

            var acertou = achaPalavra(caminhoAtual);

            if (acertou) {
                marcarEncontrada(acertou, caminhoAtual);
            } else {
                flashErro(caminhoAtual);
            }

            limparSelecao();
            ancora = null;
            caminhoAtual = null;
        }

        jogo.querySelectorAll(''[data-cp-celula]'').forEach(function (celula) {
            celula.addEventListener(''pointerdown'', function (event) {
                if (celula.classList.contains(''encontrada'')) {
                    return;
                }

                event.preventDefault();

                var r = parseInt(celula.getAttribute(''data-r''), 10);
                var c = parseInt(celula.getAttribute(''data-c''), 10);

                ativo = true;
                moveu = false;

                if (!ancora) {
                    iniciarSelecao(r, c);

                    return;
                }

                if (r === ancora.r && c === ancora.c) {
                    cancelarSelecao();

                    return;
                }

                atualizarCaminho(r, c);
                finalizarSelecao();
            });
        });

        jogo.addEventListener(''pointermove'', function (event) {
            if (!ativo || !ancora) {
                return;
            }

            var alvo = document.elementFromPoint(event.clientX, event.clientY);
            var celula = alvo ? alvo.closest(''[data-cp-celula]'') : null;

            if (!celula || !jogo.contains(celula) || celula.classList.contains(''encontrada'')) {
                return;
            }

            moveu = true;

            var r = parseInt(celula.getAttribute(''data-r''), 10);
            var c = parseInt(celula.getAttribute(''data-c''), 10);

            atualizarCaminho(r, c);
        });

        document.addEventListener(''pointerup'', function () {
            if (!ativo) {
                return;
            }

            ativo = false;

            if (moveu) {
                finalizarSelecao();
            }
        });

        document.addEventListener(''pointercancel'', function () {
            ativo = false;
        });
    });
})();
</script>',
     NULL, 'publicado', NOW());

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-completar" data-completar>
<p class="kids-quiz-progresso" data-completar-progresso>0 de 4 certas</p>
<div class="kids-completar-item" data-completar-item data-resposta="pastor">
<p class="kids-completar-frase">1. "O Senhor é o meu <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false">, nada me faltará."</p>
<p class="kids-completar-ref">Salmos 23:1</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
<div class="kids-completar-item" data-completar-item data-resposta="fortalece">
<p class="kids-completar-frase">2. "Tudo posso naquele que me <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false">."</p>
<p class="kids-completar-ref">Filipenses 4:13</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
<div class="kids-completar-item" data-completar-item data-resposta="coração">
<p class="kids-completar-frase">3. "Tudo o que fizerem, façam de <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false">, como para o Senhor."</p>
<p class="kids-completar-ref">Colossenses 3:23</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
<div class="kids-completar-item" data-completar-item data-resposta="coração">
<p class="kids-completar-frase">4. "Confie no Senhor de todo o <input type="text" class="kids-completar-input" data-completar-input autocomplete="off" spellcheck="false">."</p>
<p class="kids-completar-ref">Provérbios 3:5</p>
<button type="button" class="kids-quiz-ajuda-btn" data-completar-conferir>Conferir</button>
<p class="kids-quiz-explicacao" data-completar-feedback hidden></p>
</div>
</div>
<script>
(function () {
    // O formulario de "Concluir" fica depois deste bloco no HTML (fora
    // do texto_conteudo), entao ainda nao existe no DOM quando este
    // <script> roda - so pode ser lido apos o documento terminar de
    // parsear (mesma correcao aplicada ao quiz, ver Ajuste 155).
    document.addEventListener(''DOMContentLoaded'', iniciar);

    function normalizar(texto) {
        return texto
            .normalize(''NFD'')
            .replace(/[\\u0300-\\u036f]/g, '''')
            .trim()
            .toLowerCase();
    }

    function iniciar() {
    document.querySelectorAll(''[data-completar]'').forEach(function (bloco) {
        var itens = bloco.querySelectorAll(''[data-completar-item]'');
        var progressoEl = bloco.querySelector(''[data-completar-progresso]'');
        var formConcluir = document.querySelector(''[data-quiz-form-concluir]'');
        var total = itens.length;
        var certas = 0;

        function atualizarProgresso() {
            if (progressoEl) {
                progressoEl.textContent = certas + '' de '' + total + '' certas'';
            }

            if (certas >= total && formConcluir) {
                formConcluir.hidden = false;
            }
        }

        itens.forEach(function (item) {
            var input = item.querySelector(''[data-completar-input]'');
            var botao = item.querySelector(''[data-completar-conferir]'');
            var feedback = item.querySelector(''[data-completar-feedback]'');
            var esperado = normalizar(item.getAttribute(''data-resposta''));
            var resolvido = false;

            function conferir() {
                if (resolvido) {
                    return;
                }

                var certo = normalizar(input.value) === esperado;
                feedback.hidden = false;
                feedback.classList.remove(''correta'', ''errada'');

                if (certo) {
                    feedback.classList.add(''correta'');
                    feedback.textContent = ''✅ Isso mesmo! A resposta é "'' + item.getAttribute(''data-resposta'') + ''".'';
                    input.disabled = true;
                    botao.disabled = true;
                    resolvido = true;
                    certas++;
                    atualizarProgresso();
                } else {
                    feedback.classList.add(''errada'');
                    feedback.textContent = ''❌ Quase! Tente de novo.'';
                }
            }

            botao.addEventListener(''click'', conferir);
            input.addEventListener(''keydown'', function (event) {
                if (event.key === ''Enter'') {
                    event.preventDefault();
                    conferir();
                }
            });
        });

        atualizarProgresso();
    });
    }
})();
</script>'
WHERE tipo = 'atividade' AND origem = 'kadosys' AND titulo = 'Complete o Versículo';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-ligar" data-ligar>
<p class="kids-quiz-progresso" data-ligar-progresso>0 de 5 pares certos</p>
<div class="kids-ligar-colunas">
<div class="kids-ligar-coluna">
<button type="button" class="kids-ligar-item" data-ligar-item data-par="noe" data-lado="esq">Noé</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="davi" data-lado="esq">Davi</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="jonas" data-lado="esq">Jonas</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="daniel" data-lado="esq">Daniel</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="moises" data-lado="esq">Moisés</button>
</div>
<div class="kids-ligar-coluna">
<button type="button" class="kids-ligar-item" data-ligar-item data-par="moises" data-lado="dir">abriu o Mar Vermelho</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="jonas" data-lado="dir">foi engolido por um peixe</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="noe" data-lado="dir">construiu uma arca</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="daniel" data-lado="dir">ficou na cova dos leões</button>
<button type="button" class="kids-ligar-item" data-ligar-item data-par="davi" data-lado="dir">enfrentou um gigante</button>
</div>
</div>
</div>
<script>
(function () {
    // Mesma correcao do quiz (ver Ajuste 155): o form de "Concluir" vem
    // depois no HTML, entao so da pra ler ele depois do DOMContentLoaded.
    document.addEventListener(''DOMContentLoaded'', iniciar);

    function iniciar() {
    document.querySelectorAll(''[data-ligar]'').forEach(function (bloco) {
        var itens = bloco.querySelectorAll(''[data-ligar-item]'');
        var progressoEl = bloco.querySelector(''[data-ligar-progresso]'');
        var formConcluir = document.querySelector(''[data-quiz-form-concluir]'');
        var total = bloco.querySelectorAll(''[data-lado="esq"]'').length;
        var certos = 0;
        var selecionado = null;

        function atualizarProgresso() {
            if (progressoEl) {
                progressoEl.textContent = certos + '' de '' + total + '' pares certos'';
            }

            if (certos >= total && formConcluir) {
                formConcluir.hidden = false;
            }
        }

        function limparSelecao() {
            itens.forEach(function (item) {
                if (!item.classList.contains(''par-certo'')) {
                    item.classList.remove(''selecionado'');
                }
            });
            selecionado = null;
        }

        itens.forEach(function (item) {
            item.addEventListener(''click'', function () {
                if (item.classList.contains(''par-certo'')) {
                    return;
                }

                if (!selecionado) {
                    limparSelecao();
                    selecionado = item;
                    item.classList.add(''selecionado'');

                    return;
                }

                if (selecionado === item) {
                    limparSelecao();

                    return;
                }

                if (selecionado.getAttribute(''data-lado'') === item.getAttribute(''data-lado'')) {
                    limparSelecao();
                    selecionado = item;
                    item.classList.add(''selecionado'');

                    return;
                }

                if (selecionado.getAttribute(''data-par'') === item.getAttribute(''data-par'')) {
                    selecionado.classList.add(''par-certo'');
                    selecionado.classList.remove(''selecionado'');
                    item.classList.add(''par-certo'');
                    certos++;
                    atualizarProgresso();
                    selecionado = null;
                } else {
                    var errados = [selecionado, item];
                    errados.forEach(function (el) { el.classList.add(''par-errado''); });
                    setTimeout(function () {
                        errados.forEach(function (el) { el.classList.remove(''par-errado''); });
                    }, 350);
                    limparSelecao();
                }
            });
        });

        atualizarProgresso();
    });
    }
})();
</script>'
WHERE tipo = 'atividade' AND origem = 'kadosys' AND titulo = 'Ligue o Personagem à sua História';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-desenho" data-desenho>
<p class="kids-desenho-prompt">Desenhe algo pelo que você quer agradecer a Deus hoje, ou algo que você quer pedir a Ele em oração.</p>
<canvas class="kids-desenho-canvas" data-desenho-canvas width="360" height="280"></canvas>
<div class="kids-colorir-paleta">
<button type="button" class="kids-colorir-cor ativa" data-cor="#3A2E5C" style="background-color:#3A2E5C;" aria-label="Roxo escuro"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6B6B" style="background-color:#FF6B6B;" aria-label="Vermelho"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF9F1C" style="background-color:#FF9F1C;" aria-label="Laranja"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FFD93D" style="background-color:#FFD93D;" aria-label="Amarelo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#6BCB77" style="background-color:#6BCB77;" aria-label="Verde"></button>
<button type="button" class="kids-colorir-cor" data-cor="#4CC9F0" style="background-color:#4CC9F0;" aria-label="Azul"></button>
<button type="button" class="kids-colorir-cor" data-cor="#9B5DE5" style="background-color:#9B5DE5;" aria-label="Roxo"></button>
<button type="button" class="kids-colorir-cor" data-cor="#FF6FA5" style="background-color:#FF6FA5;" aria-label="Rosa"></button>
<button type="button" class="kids-colorir-limpar" data-desenho-limpar><i class="bi bi-arrow-counterclockwise"></i> Limpar</button>
</div>
</div>
<script>
(function () {
    document.querySelectorAll(''[data-desenho]'').forEach(function (palco) {
        var canvas = palco.querySelector(''[data-desenho-canvas]'');
        var ctx = canvas.getContext(''2d'');

        function preencherBranco() {
            ctx.fillStyle = ''#FFFFFF'';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }

        preencherBranco();
        ctx.lineWidth = 6;
        ctx.lineCap = ''round'';
        ctx.lineJoin = ''round'';

        var corAtual = ''#3A2E5C'';
        var desenhando = false;
        var ultimo = null;

        function posicao(evento) {
            var rect = canvas.getBoundingClientRect();

            return {
                x: (evento.clientX - rect.left) * (canvas.width / rect.width),
                y: (evento.clientY - rect.top) * (canvas.height / rect.height),
            };
        }

        canvas.addEventListener(''pointerdown'', function (evento) {
            desenhando = true;
            ultimo = posicao(evento);
            canvas.setPointerCapture(evento.pointerId);
        });

        canvas.addEventListener(''pointermove'', function (evento) {
            if (!desenhando) {
                return;
            }

            var atual = posicao(evento);
            ctx.strokeStyle = corAtual;
            ctx.beginPath();
            ctx.moveTo(ultimo.x, ultimo.y);
            ctx.lineTo(atual.x, atual.y);
            ctx.stroke();
            ultimo = atual;
        });

        [''pointerup'', ''pointercancel'', ''pointerleave''].forEach(function (nomeEvento) {
            canvas.addEventListener(nomeEvento, function () {
                desenhando = false;
            });
        });

        palco.querySelectorAll(''[data-cor]'').forEach(function (botao) {
            botao.addEventListener(''click'', function () {
                palco.querySelectorAll(''[data-cor]'').forEach(function (b) { b.classList.remove(''ativa''); });
                botao.classList.add(''ativa'');
                corAtual = botao.getAttribute(''data-cor'');
            });
        });

        var limpar = palco.querySelector(''[data-desenho-limpar]'');

        if (limpar) {
            limpar.addEventListener(''click'', preencherBranco);
        }
    });
})();
</script>'
WHERE tipo = 'atividade' AND origem = 'kadosys' AND titulo = 'Desenhe a sua Oração';


-- Migracao 064: modo crianca com mais conteudo, niveis, som e forca
-- de conclusao (ver database/migrations/064_kids_mais_conteudo_niveis_sons.sql).

-- Migracao 064: modo crianca com mais conteudo, niveis, som e forca de
-- conclusao (ver kids-sons.js, kids-jogo-memoria.js, kids-jogo-trivia.js,
-- kids-jogo-cacapalavras.js e kids-interacoes.js em public/assets/js/).
--
-- Parte 1: retrofit dos jogos "jogo" existentes (que ate aqui nunca
-- tinham gate de conclusao - dava pra clicar Concluir sem jogar nada)
-- para os motores genericos com fases/rodadas, e consolidacao dos 3
-- caca-palavras (que tinham o algoritmo inteiro duplicado em cada linha)
-- no motor global kids-jogo-cacapalavras.js.

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-jogo-memoria" data-jogo-memoria data-fases=''[["🐘", "🦒", "🦁", "🐯"], ["🐘", "🦒", "🦁", "🐯", "🐻", "🐵"], ["🐘", "🦒", "🦁", "🐯", "🐻", "🐵", "🦓", "🦌"]]''>
<p class="kids-jogo-status" data-memoria-status>Fase 1 de 3 — encontre os pares! 🧠</p>
<div class="kids-memoria-grade" data-memoria-grade></div>
</div>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Monte a Arca de Noé';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-jogo-memoria" data-jogo-memoria data-fases=''[["📖", "✝️", "🕊️", "⭐"], ["📖", "✝️", "🕊️", "⭐", "🙏", "👑"], ["📖", "✝️", "🕊️", "⭐", "🙏", "👑", "🌈", "🐑"]]''>
<p class="kids-jogo-status" data-memoria-status>Fase 1 de 3 — encontre os pares! 🧠</p>
<div class="kids-memoria-grade" data-memoria-grade></div>
</div>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Memória Bíblica';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo": "Rodada 1: Aquecimento", "perguntas": [{"pergunta": "Quantos discípulos Jesus escolheu?", "alternativas": ["12", "7", "20", "3"], "correta": 0}, {"pergunta": "Quem escreveu muitos Salmos?", "alternativas": ["Davi", "Golias", "Faraó", "Herodes"], "correta": 0}, {"pergunta": "Em que cidade Jesus nasceu?", "alternativas": ["Belém", "Nazaré", "Jerusalém", "Roma"], "correta": 0}, {"pergunta": "Quem foi engolido por um grande peixe?", "alternativas": ["Jonas", "Pedro", "Paulo", "Elias"], "correta": 0}]}, {"titulo": "Rodada 2: Desafio Final", "perguntas": [{"pergunta": "Quantos dias Deus levou pra criar o mundo?", "alternativas": ["6", "3", "40", "10"], "correta": 0}, {"pergunta": "Quem atravessou o Mar Vermelho com o povo de Israel?", "alternativas": ["Moisés", "Josué", "Davi", "Sansão"], "correta": 0}, {"pergunta": "Qual é o primeiro livro da Bíblia?", "alternativas": ["Gênesis", "Êxodo", "Salmos", "Mateus"], "correta": 0}, {"pergunta": "Quem traiu Jesus por 30 moedas de prata?", "alternativas": ["Judas", "Pedro", "Tomé", "João"], "correta": 0}]}]''>
<div class="kids-corrida-cabecalho">
<span data-trivia-titulo>Rodada 1 de 2</span>
<span class="kids-corrida-estrelas" data-trivia-estrelas>⭐ 0/8</span>
</div>
<div data-trivia-perguntas></div>
</div>'
    WHERE tipo = 'jogo' AND origem = 'kadosys' AND titulo = 'Corrida da Fé';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">N</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="PEDRO">PEDRO</span>
<span class="kids-cp-palavra" data-cp-palavra="ANDRÉ">ANDRÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="TIAGO">TIAGO</span>
<span class="kids-cp-palavra" data-cp-palavra="JOÃO">JOÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="FILIPE">FILIPE</span>
<span class="kids-cp-palavra" data-cp-palavra="TOMÉ">TOMÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="MATEUS">MATEUS</span>
<span class="kids-cp-palavra" data-cp-palavra="JUDAS">JUDAS</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"FILIPE","cells":[[5,3],[4,3],[3,3],[2,3],[1,3],[0,3]],"achada":false},{"word":"MATEUS","cells":[[8,1],[8,2],[8,3],[8,4],[8,5],[8,6]],"achada":false},{"word":"PEDRO","cells":[[1,4],[2,4],[3,4],[4,4],[5,4]],"achada":false},{"word":"ANDRÉ","cells":[[3,8],[4,7],[5,6],[6,5],[7,4]],"achada":false},{"word":"TIAGO","cells":[[9,4],[9,5],[9,6],[9,7],[9,8]],"achada":false},{"word":"JUDAS","cells":[[4,0],[3,0],[2,0],[1,0],[0,0]],"achada":false},{"word":"JOÃO","cells":[[1,2],[2,2],[3,2],[4,2]],"achada":false},{"word":"TOMÉ","cells":[[2,8],[3,7],[4,6],[5,5]],"achada":false}]</script>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Caça-Nomes: Os 12 Discípulos';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">E</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="NOÉ">NOÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="MOISÉS">MOISÉS</span>
<span class="kids-cp-palavra" data-cp-palavra="JOSUÉ">JOSUÉ</span>
<span class="kids-cp-palavra" data-cp-palavra="DAVI">DAVI</span>
<span class="kids-cp-palavra" data-cp-palavra="ESTER">ESTER</span>
<span class="kids-cp-palavra" data-cp-palavra="DANIEL">DANIEL</span>
<span class="kids-cp-palavra" data-cp-palavra="SANSÃO">SANSÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="GIDEÃO">GIDEÃO</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"MOISÉS","cells":[[7,7],[6,6],[5,5],[4,4],[3,3],[2,2]],"achada":false},{"word":"DANIEL","cells":[[7,6],[7,5],[7,4],[7,3],[7,2],[7,1]],"achada":false},{"word":"SANSÃO","cells":[[0,9],[1,9],[2,9],[3,9],[4,9],[5,9]],"achada":false},{"word":"GIDEÃO","cells":[[5,8],[4,7],[3,6],[2,5],[1,4],[0,3]],"achada":false},{"word":"JOSUÉ","cells":[[6,0],[5,0],[4,0],[3,0],[2,0]],"achada":false},{"word":"ESTER","cells":[[7,2],[6,2],[5,2],[4,2],[3,2]],"achada":false},{"word":"DAVI","cells":[[9,4],[9,5],[9,6],[9,7]],"achada":false},{"word":"NOÉ","cells":[[0,8],[1,8],[2,8]],"achada":false}]</script>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Caça-Nomes: Heróis do Velho Testamento';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(11, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="10">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="10">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">Z</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="10">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="10">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="10">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="10">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">Ê</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="10">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">Í</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="10">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">Ã</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="10">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="10">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="0">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="3">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="4">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="6">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="7">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="8">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="9">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="10" data-c="10">D</button>
</div>
<div class="kids-cp-lista">
<span class="kids-cp-palavra" data-cp-palavra="AMOR">AMOR</span>
<span class="kids-cp-palavra" data-cp-palavra="ALEGRIA">ALEGRIA</span>
<span class="kids-cp-palavra" data-cp-palavra="PAZ">PAZ</span>
<span class="kids-cp-palavra" data-cp-palavra="PACIÊNCIA">PACIÊNCIA</span>
<span class="kids-cp-palavra" data-cp-palavra="BONDADE">BONDADE</span>
<span class="kids-cp-palavra" data-cp-palavra="FIDELIDADE">FIDELIDADE</span>
<span class="kids-cp-palavra" data-cp-palavra="MANSIDÃO">MANSIDÃO</span>
<span class="kids-cp-palavra" data-cp-palavra="DOMÍNIO">DOMÍNIO</span>
</div>
<script type="application/json" data-cp-dados>[{"word":"FIDELIDADE","cells":[[0,9],[1,9],[2,9],[3,9],[4,9],[5,9],[6,9],[7,9],[8,9],[9,9]],"achada":false},{"word":"PACIÊNCIA","cells":[[2,0],[3,1],[4,2],[5,3],[6,4],[7,5],[8,6],[9,7],[10,8]],"achada":false},{"word":"MANSIDÃO","cells":[[2,1],[3,2],[4,3],[5,4],[6,5],[7,6],[8,7],[9,8]],"achada":false},{"word":"ALEGRIA","cells":[[8,10],[7,10],[6,10],[5,10],[4,10],[3,10],[2,10]],"achada":false},{"word":"BONDADE","cells":[[7,7],[6,6],[5,5],[4,4],[3,3],[2,2],[1,1]],"achada":false},{"word":"DOMÍNIO","cells":[[4,1],[5,1],[6,1],[7,1],[8,1],[9,1],[10,1]],"achada":false},{"word":"AMOR","cells":[[4,5],[3,4],[2,3],[1,2]],"achada":false},{"word":"PAZ","cells":[[2,7],[2,6],[2,5]],"achada":false}]</script>
</div>'
    WHERE origem = 'kadosys' AND tipo = 'jogo' AND titulo = 'Caça-Nomes: Frutos do Espírito';

-- Parte 2: conteudo novo usando os motores com niveis (memoria, trivia,
-- caca-palavras) + mais quizzes (o quiz ja tinha gate de conclusao
-- nativo desde antes, so precisava de mais variedade de temas).

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Memória: Milagres de Jesus', 'Jogo da memoria com 3 fases, dos pares faceis aos dificeis, sobre os milagres de Jesus.', 'Novo Testamento', 'Milagres', NULL, 'Jesus', 'medio', 5, 11, 6, 20, 12,
     '<div class="kids-jogo-memoria" data-jogo-memoria data-fases=''[["🐟", "🍞", "💧", "🍷"], ["🐟", "🍞", "💧", "🍷", "🌊", "👁️"], ["🐟", "🍞", "💧", "🍷", "🌊", "👁️", "🚶", "⚰️"]]''>
<p class="kids-jogo-status" data-memoria-status>Fase 1 de 3 — encontre os pares! 🧠</p>
<div class="kids-memoria-grade" data-memoria-grade></div>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('jogo', 'kadosys', 'Trilha da Criação', 'Trivia em 2 rodadas sobre os 7 dias da Criação - so avanca de rodada acertando tudo.', 'Velho Testamento', 'Criação', 'Gênesis', NULL, 'medio', 5, 11, 6, 20, 12,
     '<div class="kids-corrida" data-jogo-trivia data-rodadas=''[{"titulo": "Rodada 1: Os primeiros dias", "perguntas": [{"pergunta": "O que Deus criou no 1º dia?", "alternativas": ["A luz", "Os animais", "O homem", "As plantas"], "correta": 0}, {"pergunta": "O que Deus criou no 2º dia?", "alternativas": ["O céu", "O sol", "Os peixes", "As aves"], "correta": 0}, {"pergunta": "No 3º dia, além da terra seca, o que mais Deus criou?", "alternativas": ["As plantas", "Os pássaros", "As estrelas", "O homem"], "correta": 0}, {"pergunta": "O que Deus criou no 4º dia?", "alternativas": ["Sol, lua e estrelas", "Os peixes", "Os répteis", "Adão"], "correta": 0}]}, {"titulo": "Rodada 2: A vida aparece", "perguntas": [{"pergunta": "O que Deus criou no 5º dia?", "alternativas": ["Peixes e aves", "Os animais terrestres", "O homem", "As montanhas"], "correta": 0}, {"pergunta": "Quem foi criado no 6º dia, junto com os animais?", "alternativas": ["O homem", "Os anjos", "As estrelas", "Os peixes"], "correta": 0}, {"pergunta": "O que Deus fez no 7º dia?", "alternativas": ["Descansou", "Criou o mar", "Criou o sol", "Criou Eva"], "correta": 0}, {"pergunta": "Em qual livro da Bíblia está a história da Criação?", "alternativas": ["Gênesis", "Êxodo", "Salmos", "Apocalipse"], "correta": 0}]}]''>
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
    ('jogo', 'kadosys', 'Caça-Nomes: Mulheres da Bíblia', 'Encontre na grade os nomes de mulheres corajosas da Bíblia.', 'Geral', 'Mulheres da fé', NULL, NULL, 'medio', 6, 12, 8, 20, 12,
     '<div class="kids-cacapalavras" data-cacapalavras>
<p class="kids-cp-status" data-cp-status>0/8 encontradas</p>
<div class="kids-cp-grade" style="grid-template-columns: repeat(10, 1fr);">
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="0">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="1">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="2">Z</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="4">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="5">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="7">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="8">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="0" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="0">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="3">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="4">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="5">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="6">N</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="7">Õ</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="8">Z</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="1" data-c="9">Í</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="0">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="1">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="2">Q</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="3">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="4">P</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="6">Q</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="7">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="8">Z</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="2" data-c="9">X</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="0">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="1">O</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="2">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="3">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="4">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="5">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="6">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="3" data-c="9">Ú</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="1">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="3">Õ</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="4">F</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="5">D</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="6">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="7">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="8">K</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="4" data-c="9">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="0">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="2">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="3">Y</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="4">Y</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="5">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="6">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="7">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="8">I</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="5" data-c="9">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="0">Í</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="2">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="3">Q</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="4">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="5">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="6">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="7">V</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="8">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="6" data-c="9">S</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="0">É</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="1">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="2">C</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="3">K</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="4">Í</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="5">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="6">Q</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="7">Ú</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="8">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="7" data-c="9">Ú</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="0">G</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="1">R</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="2">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="3">Q</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="4">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="5">E</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="6">L</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="7">T</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="8">Ú</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="8" data-c="9">M</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="0">J</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="1">X</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="2">K</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="3">A</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="4">U</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="5">Õ</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="6">B</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="7">H</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="8">X</button>
<button type="button" class="kids-cp-celula" data-cp-celula data-r="9" data-c="9">T</button>
</div>
<div class="kids-cp-lista" data-cp-lista>
<span class="kids-cp-palavra" data-cp-palavra="ESTER">ESTER</span>
<span class="kids-cp-palavra" data-cp-palavra="RUTE">RUTE</span>
<span class="kids-cp-palavra" data-cp-palavra="SARA">SARA</span>
<span class="kids-cp-palavra" data-cp-palavra="MARIA">MARIA</span>
<span class="kids-cp-palavra" data-cp-palavra="ANA">ANA</span>
<span class="kids-cp-palavra" data-cp-palavra="DEBORA">DEBORA</span>
<span class="kids-cp-palavra" data-cp-palavra="REBECA">REBECA</span>
<span class="kids-cp-palavra" data-cp-palavra="RAQUEL">RAQUEL</span>
</div>
<script type="application/json" data-cp-dados>[{"word": "DEBORA", "cells": [[0, 4], [0, 5], [0, 6], [0, 7], [0, 8], [0, 9]], "achada": false}, {"word": "REBECA", "cells": [[3, 2], [4, 2], [5, 2], [6, 2], [7, 2], [8, 2]], "achada": false}, {"word": "RAQUEL", "cells": [[8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 6]], "achada": false}, {"word": "ESTER", "cells": [[3, 3], [3, 4], [3, 5], [3, 6], [3, 7]], "achada": false}, {"word": "MARIA", "cells": [[5, 5], [5, 6], [5, 7], [5, 8], [5, 9]], "achada": false}, {"word": "RUTE", "cells": [[1, 0], [2, 0], [3, 0], [4, 0]], "achada": false}, {"word": "SARA", "cells": [[4, 1], [5, 1], [6, 1], [7, 1]], "achada": false}, {"word": "ANA", "cells": [[0, 3], [1, 4], [2, 5]], "achada": false}]</script>
</div>',
     NULL, 'publicado', NOW());

INSERT INTO kids_conteudos
    (tipo, origem, titulo, descricao, categoria, tema, livro_biblico, personagem, dificuldade, idade_min, idade_max, duracao_minutos, xp_recompensa, moedas_recompensa, texto_conteudo, quiz_perguntas, status, created_at)
VALUES
    ('quiz', 'kadosys', 'Quiz: Mulheres da Bíblia', 'Teste o que você sabe sobre as mulheres corajosas e cheias de fé da Bíblia!', 'Geral', 'Mulheres da fé', NULL, NULL, 'medio', 6, 12, 6, 20, 10,
     NULL,
     '[{"pergunta": "Quem foi a rainha que salvou seu povo arriscando a própria vida?", "alternativas": ["Ester", "Débora", "Rute", "Sara"], "correta": 0}, {"pergunta": "Qual mulher seguiu sua sogra Noemi e disse ''onde tu fores, irei''?", "alternativas": ["Rute", "Ana", "Raquel", "Rebeca"], "correta": 0}, {"pergunta": "Quem foi a mãe de Jesus?", "alternativas": ["Maria", "Marta", "Ana", "Eva"], "correta": 0}, {"pergunta": "Qual profetisa e juíza liderou Israel?", "alternativas": ["Débora", "Ester", "Sara", "Rute"], "correta": 0}, {"pergunta": "Quem foi a esposa de Abraão que teve um filho em idade avançada?", "alternativas": ["Sara", "Rebeca", "Raquel", "Lia"], "correta": 0}, {"pergunta": "Qual mulher orou muito por um filho e teve Samuel?", "alternativas": ["Ana", "Ester", "Débora", "Rute"], "correta": 0}]',
     'publicado', NOW()),
    ('quiz', 'kadosys', 'Quiz: Oração e Mandamentos', 'Um quiz sobre oração, os mandamentos e o maior ensino de Jesus.', 'Valores', 'Oração e obediência', NULL, 'Jesus', 'medio', 6, 12, 6, 20, 10,
     NULL,
     '[{"pergunta": "Qual é o mandamento mais importante segundo Jesus?", "alternativas": ["Amar a Deus sobre todas as coisas", "Não roubar", "Guardar o sábado", "Não mentir"], "correta": 0}, {"pergunta": "Quantos mandamentos Deus deu a Moisés no monte Sinai?", "alternativas": ["10", "7", "12", "5"], "correta": 0}, {"pergunta": "O que Jesus ensinou que devemos fazer com nossos inimigos?", "alternativas": ["Amar e orar por eles", "Ignorar", "Evitar", "Competir"], "correta": 0}, {"pergunta": "Qual oração Jesus ensinou aos discípulos?", "alternativas": ["Pai Nosso", "Ave Maria", "Salmo 23", "Credo"], "correta": 0}, {"pergunta": "Segundo a Bíblia, o que devemos fazer antes de pedir algo a Deus?", "alternativas": ["Agradecer e orar com fé", "Duvidar", "Desistir", "Nada"], "correta": 0}, {"pergunta": "Qual fruto do Espírito ajuda a esperar com calma?", "alternativas": ["Paciência", "Raiva", "Pressa", "Orgulho"], "correta": 0}]',
     'publicado', NOW());

-- Migracao 065: correcoes de qualidade no modo crianca (ver
-- database/migrations/065_kids_correcoes_qualidade.sql).

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

-- Migracao 066: gate universal de conclusao no modo crianca + upload de
-- foto do desafio (ver database/migrations/066_kids_gate_universal.sql).

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-plano" data-plano-leitura>
<p class="kids-plano-instrucao">Marque cada dia depois de ler e refletir no versículo! 📖</p>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 1:</strong> Salmos 23:1 - "O Senhor é o meu pastor, nada me faltará."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 2:</strong> Salmos 118:24 - "Este é o dia que o Senhor fez; alegremo-nos e regozijemo-nos nele."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 3:</strong> Salmos 46:1 - "Deus é o nosso refúgio e fortaleza."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 4:</strong> Salmos 100:5 - "O Senhor é bom; a sua misericórdia dura para sempre."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 5:</strong> Salmos 121:2 - "O meu socorro vem do Senhor, que fez os céus e a terra."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 6:</strong> Salmos 34:8 - "Provai e vede que o Senhor é bom."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 7:</strong> Salmos 139:14 - "Eu te louvarei, porque de um modo assombroso e maravilhoso fui formado."</span></label>
</div>' WHERE origem = 'kadosys' AND tipo = 'plano_leitura' AND titulo = '7 dias com os Salmos';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-plano" data-plano-leitura>
<p class="kids-plano-instrucao">Marque cada dia depois de ler e refletir no versículo! 📖</p>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 1:</strong> Provérbios 1:7 - "O temor do Senhor é o princípio do conhecimento."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 2:</strong> Provérbios 3:5 - "Confie no Senhor de todo o coração."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 3:</strong> Provérbios 15:1 - "A resposta mansa desvia o furor."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 4:</strong> Provérbios 17:17 - "Em todo o tempo ama o amigo."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 5:</strong> Provérbios 18:10 - "O nome do Senhor é uma torre forte."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 6:</strong> Provérbios 22:6 - "Ensina a criança no caminho em que deve andar."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 7:</strong> Provérbios 31:30 - "A mulher que teme ao Senhor, essa será louvada."</span></label>
</div>' WHERE origem = 'kadosys' AND tipo = 'plano_leitura' AND titulo = '7 dias com Provérbios';

UPDATE kids_conteudos SET texto_conteudo = '<div class="kids-plano" data-plano-leitura>
<p class="kids-plano-instrucao">Marque cada dia depois de ler e refletir no versículo! 📖</p>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 1: Amor</strong> - 1 Coríntios 13:4 - "O amor é paciente, o amor é bondoso."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 2: Alegria</strong> - Neemias 8:10 - "A alegria do Senhor é a força de vocês."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 3: Paz</strong> - João 14:27 - "Deixo-lhes a paz; a minha paz lhes dou."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 4: Paciência</strong> - Tiago 1:4 - "Que a perseverança conclua a sua obra."</span></label>
<label class="kids-plano-dia"><input type="checkbox" data-plano-dia><span><strong>Dia 5: Domínio próprio</strong> - Provérbios 25:28 - "Como cidade derrubada, sem muro, é o homem que não sabe controlar-se."</span></label>
</div>' WHERE origem = 'kadosys' AND tipo = 'plano_leitura' AND titulo = '5 dias sobre o Fruto do Espírito';

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


-- Ajuste 178: emblemas/conquistas por marcos especiais (pedido do
-- usuario, item da lista de ideias em aberto) - so guarda QUAIS
-- emblemas cada crianca ja conquistou; o catalogo (nome, emoji,
-- descricao, criterio) e estatico em PHP (Igrejas\Models\KidsEmblema),
-- mesmo padrao ja usado no catalogo do avatar (KidsAvatar).

CREATE TABLE IF NOT EXISTS kids_emblemas_conquistados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    emblema_slug VARCHAR(60) NOT NULL,
    conquistado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY kids_emblemas_conquistados_unique (crianca_id, emblema_slug),
    CONSTRAINT fk_kids_emblemas_conquistados_crianca
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Ajuste 180: Biblia Interativa da Biblioteca Kids (pedido do usuario,
-- Prioridade 7 da lista colada no chat) - navegacao pelos 66 livros
-- (ja seedados desde a migracao 005) com o texto de biblia_versiculos
-- (importado a parte via database/seed_biblia.php - ver
-- Igrejas\Models\BibliaVersiculo::textoImportado()). Esta tabela so
-- guarda quais capitulos cada crianca ja leu, pra progresso visual e
-- bonus de XP na primeira leitura de cada capitulo.

CREATE TABLE IF NOT EXISTS kids_biblia_leituras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    livro_id TINYINT UNSIGNED NOT NULL,
    capitulo SMALLINT UNSIGNED NOT NULL,
    lido_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY kids_biblia_leituras_unique (crianca_id, livro_id, capitulo),
    KEY kids_biblia_leituras_crianca_livro_index (crianca_id, livro_id),
    CONSTRAINT fk_kids_biblia_leituras_crianca
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT fk_kids_biblia_leituras_livro
        FOREIGN KEY (livro_id) REFERENCES biblia_livros (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Ajuste 182: Mapa Biblico da Biblioteca Kids (pedido do usuario,
-- Prioridade 8 da lista colada no chat) - mapa ilustrado e clicavel
-- com os lugares mais importantes da Biblia. O catalogo (nome, emoji,
-- descricao, posicao no mapa) e estatico em PHP (Igrejas\Models\
-- KidsMapaLocal), mesmo padrao ja usado no catalogo de emblemas -
-- esta tabela so guarda quais locais cada crianca ja explorou.

CREATE TABLE IF NOT EXISTS kids_mapa_explorados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    local_slug VARCHAR(60) NOT NULL,
    explorado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY kids_mapa_explorados_unique (crianca_id, local_slug),
    CONSTRAINT fk_kids_mapa_explorados_crianca
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
