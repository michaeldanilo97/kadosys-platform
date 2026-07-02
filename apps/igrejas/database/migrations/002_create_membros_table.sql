-- KADOSYS Igrejas - Migracao 002
-- Tabela do modulo Membros (primeiro modulo de negocio da v2, conforme
-- roadmap.md). Demais tabelas (ministerios, cultos, financeiro, etc.)
-- serao criadas em migracoes futuras, junto com cada modulo.

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
