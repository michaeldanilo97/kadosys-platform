-- CRM basico (aniversariantes/clientes inativos) e portfolio de fotos
-- dos profissionais.

ALTER TABLE clientes
    ADD COLUMN data_nascimento DATE NULL AFTER email;

-- Fotos do trabalho de um profissional (antes/depois, cortes feitos)
-- mostradas na pagina publica de agendamento.
CREATE TABLE IF NOT EXISTS portfolio_fotos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NOT NULL,
    foto_path VARCHAR(255) NOT NULL,
    legenda VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY portfolio_fotos_barbearia_id_index (barbearia_id),
    KEY portfolio_fotos_profissional_id_index (profissional_id),
    CONSTRAINT portfolio_fotos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT portfolio_fotos_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
