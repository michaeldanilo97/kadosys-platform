-- KADOSYS Barbearias - Migracao 003
-- Area do cliente: login proprio (senha em clientes) + avaliacoes de
-- atendimento.
--
-- So precisa rodar esta migracao se `install.sql` JA foi executado
-- antes no banco `kadosys1_barbearias`. Se ainda nao rodou nenhuma
-- vez, NAO precisa desta migracao - a versao atual do install.sql ja
-- inclui tudo isso desde o inicio.

ALTER TABLE clientes
    ADD COLUMN password VARCHAR(255) NULL AFTER email;

CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    agendamento_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NOT NULL,
    nota TINYINT UNSIGNED NOT NULL,
    comentario TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY avaliacoes_agendamento_id_unique (agendamento_id),
    KEY avaliacoes_barbearia_id_index (barbearia_id),
    KEY avaliacoes_profissional_id_index (profissional_id),
    CONSTRAINT avaliacoes_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT avaliacoes_agendamento_id_foreign
        FOREIGN KEY (agendamento_id) REFERENCES agendamentos (id) ON DELETE CASCADE,
    CONSTRAINT avaliacoes_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE,
    CONSTRAINT avaliacoes_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE,
    CONSTRAINT avaliacoes_nota_check CHECK (nota BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
