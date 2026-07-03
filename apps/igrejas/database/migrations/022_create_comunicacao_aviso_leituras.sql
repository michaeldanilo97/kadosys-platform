-- KADOSYS Igrejas - Migracao 022
-- Controle de leitura dos avisos do modulo Comunicacao por usuario:
-- uma linha aqui quer dizer "este usuario ja abriu este aviso" - usado
-- pra tirar o indicador de "novo" na lista de avisos da barra lateral
-- do painel assim que o usuario abre o aviso (ver
-- ComunicacaoAviso::paraSidebar()/marcarComoLido()).

CREATE TABLE IF NOT EXISTS comunicacao_aviso_leituras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aviso_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    lido_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY comunicacao_aviso_leituras_unique (aviso_id, user_id),
    CONSTRAINT comunicacao_aviso_leituras_aviso_id_foreign
        FOREIGN KEY (aviso_id) REFERENCES comunicacao_avisos (id) ON DELETE CASCADE,
    CONSTRAINT comunicacao_aviso_leituras_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
