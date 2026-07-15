-- Programa de fidelidade: pontos por real gasto (configuravel por
-- barbearia, NULL = desativado), resgatados por recompensas
-- cadastradas. Ver Barbearias\Controllers\FidelidadeController.
ALTER TABLE barbearias
    ADD COLUMN fidelidade_pontos_por_real DECIMAL(6, 2) NULL AFTER plano_agendado;

ALTER TABLE clientes
    ADD COLUMN pontos_fidelidade INT UNSIGNED NOT NULL DEFAULT 0 AFTER data_nascimento;

CREATE TABLE IF NOT EXISTS fidelidade_recompensas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    pontos_necessarios INT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY fidelidade_recompensas_barbearia_id_index (barbearia_id),
    CONSTRAINT fidelidade_recompensas_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extrato: cada ganho (atendimento pago) ou resgate (recompensa
-- trocada) vira uma linha aqui, pra dar historico auditavel do saldo
-- de pontos de cada cliente.
CREATE TABLE IF NOT EXISTS fidelidade_movimentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    tipo ENUM('ganho', 'resgate') NOT NULL,
    pontos INT NOT NULL,
    agendamento_id INT UNSIGNED NULL,
    recompensa_id INT UNSIGNED NULL,
    descricao VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY fidelidade_movimentos_barbearia_id_index (barbearia_id),
    KEY fidelidade_movimentos_cliente_id_index (cliente_id),
    CONSTRAINT fidelidade_movimentos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT fidelidade_movimentos_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE,
    CONSTRAINT fidelidade_movimentos_agendamento_id_foreign
        FOREIGN KEY (agendamento_id) REFERENCES agendamentos (id) ON DELETE SET NULL,
    CONSTRAINT fidelidade_movimentos_recompensa_id_foreign
        FOREIGN KEY (recompensa_id) REFERENCES fidelidade_recompensas (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
