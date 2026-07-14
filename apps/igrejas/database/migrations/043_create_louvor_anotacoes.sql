-- KADOSYS Igrejas - Migracao 043
-- Anotacoes PESSOAIS de cada musico em cada louvor (ex.: "usar
-- capotraste na 2a casa", "trocar pra guitarra limpa", "solo comeca no
-- segundo refrao") - uma nota por usuario por louvor, NUNCA
-- compartilhada com os demais (ver LouvorController::minhaAnotacao()).
--
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central) - mesma regra
-- das outras migracoes de modulo (ex.: 039/040/041/042).

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
