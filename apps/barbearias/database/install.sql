-- KADOSYS Barbearias - Schema inicial
-- RODAR UMA UNICA VEZ no banco kadosys1_barbearias (banco UNICO e
-- compartilhado entre todas as barbearias - diferente do KADOSYS
-- Igrejas, aqui NAO ha um banco por cliente). Toda tabela de negocio
-- (exceto a propria `barbearias`) tem uma coluna barbearia_id que
-- isola os dados de cada barbearia - todo Model PRECISA filtrar por
-- ela em toda query, ja que o banco nao faz esse isolamento sozinho.

CREATE TABLE IF NOT EXISTS barbearias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    telefone VARCHAR(20) NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY barbearias_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users.email e unico GLOBALMENTE (nao por barbearia) - cada conta de
-- acesso pertence a exatamente uma barbearia, e o login resolve direto
-- pra qual sem precisar de uma etapa de selecao (diferente do KADOSYS
-- Igrejas, que tem varios bancos e por isso pode ter o mesmo e-mail em
-- mais de um).
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'usuario') NOT NULL DEFAULT 'admin',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY users_email_unique (email),
    KEY users_barbearia_id_index (barbearia_id),
    CONSTRAINT users_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS profissionais (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    especialidade VARCHAR(150) NULL,
    telefone VARCHAR(20) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY profissionais_barbearia_id_index (barbearia_id),
    CONSTRAINT profissionais_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    preco DECIMAL(10, 2) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY servicos_barbearia_id_index (barbearia_id),
    CONSTRAINT servicos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    telefone VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY clientes_barbearia_id_index (barbearia_id),
    CONSTRAINT clientes_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agendamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NOT NULL,
    servico_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    data_hora DATETIME NOT NULL,
    status ENUM('agendado', 'concluido', 'cancelado') NOT NULL DEFAULT 'agendado',
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY agendamentos_barbearia_id_index (barbearia_id),
    KEY agendamentos_data_hora_index (data_hora),
    CONSTRAINT agendamentos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT agendamentos_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE,
    CONSTRAINT agendamentos_servico_id_foreign
        FOREIGN KEY (servico_id) REFERENCES servicos (id) ON DELETE CASCADE,
    CONSTRAINT agendamentos_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
