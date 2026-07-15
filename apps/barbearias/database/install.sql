-- KADOSYS Barbearias - Schema inicial
-- RODAR UMA UNICA VEZ no banco kadosys1_barbearias (banco UNICO e
-- compartilhado entre todas as barbearias - diferente do KADOSYS
-- Igrejas, aqui NAO ha um banco por cliente). Toda tabela de negocio
-- (exceto a propria `barbearias`) tem uma coluna barbearia_id que
-- isola os dados de cada barbearia - todo Model PRECISA filtrar por
-- ela em toda query, ja que o banco nao faz esse isolamento sozinho.

-- "status" comeca 'pendente' pra quem escolhe Pix/cartao (so vira
-- 'ativo' quando o webhook do Mercado Pago confirma o pagamento) e ja
-- nasce 'ativo' pra quem escolhe trial (sem cobranca nenhuma nos
-- primeiros dias, ver Barbearias\Models\Plano::TRIAL_DIAS). Diferente
-- do KADOSYS Igrejas, aqui NAO ha banco/subdominio pra provisionar -
-- "ativar" e so um UPDATE nesta mesma linha, sem infraestrutura
-- nenhuma de por meio, entao nao precisa de uma tabela separada de
-- "tentativas de cadastro" (plataforma_provisionamentos de Igrejas) -
-- a propria linha em barbearias, com status 'pendente', ja cumpre
-- esse papel.
CREATE TABLE IF NOT EXISTS barbearias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    telefone VARCHAR(20) NULL,
    documento_tipo ENUM('cpf', 'cnpj') NOT NULL DEFAULT 'cpf',
    documento VARCHAR(14) NOT NULL DEFAULT '',
    razao_social VARCHAR(190) NULL,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL DEFAULT 'essencial',
    metodo_pagamento ENUM('cartao', 'pix', 'trial') NOT NULL DEFAULT 'trial',
    mp_preapproval_id VARCHAR(64) NULL,
    trial_expira_em DATETIME NULL,
    proximo_vencimento DATE NULL,
    plano_agendado VARCHAR(20) NULL,
    ultimo_acesso_em DATETIME NULL,
    status ENUM('pendente', 'ativo', 'suspenso') NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY barbearias_slug_unique (slug),
    KEY barbearias_documento_index (documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cobranca Pix avulsa - cobre tanto o PRIMEIRO pagamento (cadastro
-- novo) quanto cada renovacao mensal seguinte (gerada por
-- cron/gerar_faturas_pix.php), sem distincao entre as duas - ao
-- contrario de Igrejas (que separa "primeiro pagamento" em
-- plataforma_provisionamentos de "renovacao" em plataforma_faturas),
-- aqui a barbearia ja existe com status 'pendente' desde o primeiro
-- pagamento, entao toda cobranca Pix pode viver nesta unica tabela.
-- Cartao nao gera linha aqui: a cobranca recorrente e feita pelo
-- proprio Mercado Pago via preapproval (ver barbearias.mp_preapproval_id).
CREATE TABLE IF NOT EXISTS barbearia_faturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL,
    tipo ENUM('renovacao', 'upgrade_proporcional') NOT NULL DEFAULT 'renovacao',
    valor DECIMAL(10, 2) NOT NULL,
    mp_payment_id VARCHAR(64) NULL,
    pix_qr_code TEXT NULL,
    pix_qr_code_base64 MEDIUMTEXT NULL,
    status ENUM('pendente', 'paga', 'expirada', 'cancelada') NOT NULL DEFAULT 'pendente',
    vencimento DATETIME NOT NULL,
    pago_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY barbearia_faturas_mp_payment_id_unique (mp_payment_id),
    KEY barbearia_faturas_barbearia_id_index (barbearia_id),
    CONSTRAINT barbearia_faturas_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
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
