-- Ajuste 180: Biblia Interativa da Biblioteca Kids (pedido do usuario,
-- Prioridade 7 da lista colada no chat) - navegacao pelos 66 livros
-- (ja seedados desde a migracao 005) com o texto de biblia_versiculos
-- (importado a parte via database/seed_biblia.php - ver
-- Igrejas\Models\BibliaVersiculo::textoImportado()). Esta tabela so
-- guarda quais capitulos cada crianca ja leu, pra progresso visual e
-- bonus de XP na primeira leitura de cada capitulo.

CREATE TABLE IF NOT EXISTS kids_biblia_leituras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    livro_id TINYINT UNSIGNED NOT NULL,
    capitulo SMALLINT UNSIGNED NOT NULL,
    lido_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY kids_biblia_leituras_unique (crianca_id, livro_id, capitulo),
    KEY kids_biblia_leituras_crianca_livro_index (crianca_id, livro_id),
    CONSTRAINT fk_kids_biblia_leituras_crianca
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT fk_kids_biblia_leituras_livro
        FOREIGN KEY (livro_id) REFERENCES biblia_livros (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
