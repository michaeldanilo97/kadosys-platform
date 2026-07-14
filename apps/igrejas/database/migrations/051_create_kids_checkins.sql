-- KADOSYS Igrejas - Migracao 051
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids (Fase 1 - operacional): registro de entrada e
-- saida de cada crianca (check-in feito pelo professor/recepcao ao
-- chegar na igreja). Gera um codigo de seguranca de 4 digitos entregue
-- ao responsavel na entrada - so quem apresenta o codigo (ou consta em
-- autorizados_retirada) pode retirar a crianca (conferido manualmente
-- pela equipe na tela de check-in, sem exigir o app da crianca).
-- E uma tabela propria (nao presa a um registro de Culto) pra nao
-- exigir que a equipe cadastre um culto antes de poder fazer check-in.

CREATE TABLE IF NOT EXISTS kids_checkins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    data DATE NOT NULL,
    codigo_seguranca VARCHAR(6) NOT NULL,
    hora_entrada DATETIME NOT NULL,
    hora_saida DATETIME NULL,
    entregue_por VARCHAR(150) NULL,
    retirado_por VARCHAR(150) NULL,
    observacoes TEXT NULL,
    registrado_por_user_id INT UNSIGNED NULL,
    encerrado_por_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY kids_checkins_crianca_id_index (crianca_id),
    KEY kids_checkins_data_index (data),
    CONSTRAINT kids_checkins_crianca_id_foreign
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT kids_checkins_registrado_por_user_id_foreign
        FOREIGN KEY (registrado_por_user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT kids_checkins_encerrado_por_user_id_foreign
        FOREIGN KEY (encerrado_por_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
