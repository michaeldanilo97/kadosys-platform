-- KADOSYS Igrejas - Migracao 062
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids: da mais uso as moedas alem do "Pedir ajuda" do
-- quiz (ver migracao 060/061) - agora tambem da pra comprar itens
-- exclusivos do Avatar (chapeu/acessorio/fundo/titulo) que nao dependem
-- de nivel, so de moedas acumuladas. Um item comprado fica
-- permanentemente desbloqueado pra aquela crianca (ver
-- Igrejas\Models\KidsAvatarCompra e Igrejas\Models\KidsAvatar - itens
-- do catalogo com "nivel" nulo so desbloqueiam por aqui).

CREATE TABLE kids_avatar_compras (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    crianca_id INT UNSIGNED NOT NULL,
    categoria ENUM('chapeu', 'acessorio', 'fundo', 'titulo') NOT NULL,
    slug VARCHAR(60) NOT NULL,
    custo_moedas INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kids_avatar_compras (crianca_id, categoria, slug),
    CONSTRAINT fk_kids_avatar_compras_crianca FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
