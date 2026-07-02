-- KADOSYS Igrejas - Instalacao completa do banco de dados
-- ============================================================================
-- Este arquivo reune, em ordem, TODAS as migracoes ja criadas em
-- database/migrations/ ate o momento (001, 002, 003 e 004). Ele existe
-- apenas para facilitar a instalacao inicial (rodar um unico arquivo em
-- vez de varios).
--
-- As migracoes numeradas em database/migrations/ continuam sendo a fonte
-- de verdade e o historico incremental do banco: sempre que um novo modulo
-- for implementado, uma nova migracao numerada e criada la, e este arquivo
-- e atualizado para refletir o schema completo mais recente.
--
-- Uso (escolha uma das duas formas, nunca as duas):
--   a) Instalacao nova, direto por este arquivo unico:
--        mysql -u usuario -p nome_do_banco < database/install.sql
--   b) Rodar as migracoes numeradas uma a uma, em ordem:
--        mysql -u usuario -p nome_do_banco < database/migrations/001_create_tables.sql
--        mysql -u usuario -p nome_do_banco < database/migrations/002_create_membros_table.sql
--        mysql -u usuario -p nome_do_banco < database/migrations/003_create_ministerios_tables.sql
--        mysql -u usuario -p nome_do_banco < database/migrations/004_create_cultos_tables.sql
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 001 - Autenticacao (users, remember_tokens, password_resets)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
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
