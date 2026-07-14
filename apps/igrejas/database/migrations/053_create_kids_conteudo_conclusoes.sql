-- KADOSYS Igrejas - Migracao 053
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids (Fase 2 - biblioteca de conteudo): registra quais
-- criancas ja concluiram cada conteudo (historia lida, video assistido,
-- quiz respondido etc.) - evita conceder XP/moedas duas vezes pelo
-- mesmo conteudo (UNIQUE) e da base pro futuro painel dos pais/
-- professores acompanharem o progresso.

CREATE TABLE IF NOT EXISTS kids_conteudo_conclusoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    conteudo_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY kids_conteudo_conclusoes_unique (crianca_id, conteudo_id),
    CONSTRAINT kids_conteudo_conclusoes_crianca_id_foreign
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT kids_conteudo_conclusoes_conteudo_id_foreign
        FOREIGN KEY (conteudo_id) REFERENCES kids_conteudos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
