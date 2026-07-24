-- Ajuste 182: Mapa Biblico da Biblioteca Kids (pedido do usuario,
-- Prioridade 8 da lista colada no chat) - mapa ilustrado e clicavel
-- com os lugares mais importantes da Biblia. O catalogo (nome, emoji,
-- descricao, posicao no mapa) e estatico em PHP (Igrejas\Models\
-- KidsMapaLocal), mesmo padrao ja usado no catalogo de emblemas -
-- esta tabela so guarda quais locais cada crianca ja explorou.

CREATE TABLE IF NOT EXISTS kids_mapa_explorados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    local_slug VARCHAR(60) NOT NULL,
    explorado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY kids_mapa_explorados_unique (crianca_id, local_slug),
    CONSTRAINT fk_kids_mapa_explorados_crianca
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
