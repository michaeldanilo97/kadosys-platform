-- Ajuste 178: emblemas/conquistas por marcos especiais (pedido do
-- usuario, item da lista de ideias em aberto) - so guarda QUAIS
-- emblemas cada crianca ja conquistou; o catalogo (nome, emoji,
-- descricao, criterio) e estatico em PHP (Igrejas\Models\KidsEmblema),
-- mesmo padrao ja usado no catalogo do avatar (KidsAvatar).

CREATE TABLE IF NOT EXISTS kids_emblemas_conquistados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    emblema_slug VARCHAR(60) NOT NULL,
    conquistado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY kids_emblemas_conquistados_unique (crianca_id, emblema_slug),
    CONSTRAINT fk_kids_emblemas_conquistados_crianca
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
