-- Modulo financeiro: caixa diario (abertura/fechamento) e lancamentos
-- de receita/despesa, com vinculo opcional a um agendamento (pagamento
-- de atendimento, registrado como um mini-PDV) ou a um caixa aberto.

CREATE TABLE IF NOT EXISTS caixas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NULL,
    status ENUM('aberto', 'fechado') NOT NULL DEFAULT 'aberto',
    valor_abertura DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    valor_fechamento_informado DECIMAL(10, 2) NULL,
    observacoes_abertura TEXT NULL,
    observacoes_fechamento TEXT NULL,
    aberto_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechado_em TIMESTAMP NULL,
    KEY caixas_barbearia_id_index (barbearia_id),
    KEY caixas_status_index (status),
    CONSTRAINT caixas_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT caixas_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financeiro_lancamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    caixa_id INT UNSIGNED NULL,
    agendamento_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    categoria VARCHAR(80) NOT NULL,
    forma_pagamento ENUM('dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'outro') NOT NULL DEFAULT 'outro',
    valor DECIMAL(10, 2) NOT NULL,
    descricao VARCHAR(255) NULL,
    data_lancamento DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY financeiro_lancamentos_barbearia_id_index (barbearia_id),
    KEY financeiro_lancamentos_caixa_id_index (caixa_id),
    KEY financeiro_lancamentos_data_lancamento_index (data_lancamento),
    UNIQUE KEY financeiro_lancamentos_agendamento_id_unique (agendamento_id),
    CONSTRAINT financeiro_lancamentos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT financeiro_lancamentos_caixa_id_foreign
        FOREIGN KEY (caixa_id) REFERENCES caixas (id) ON DELETE SET NULL,
    CONSTRAINT financeiro_lancamentos_agendamento_id_foreign
        FOREIGN KEY (agendamento_id) REFERENCES agendamentos (id) ON DELETE SET NULL,
    CONSTRAINT financeiro_lancamentos_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
