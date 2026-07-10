-- KADOSYS Igrejas - Migracao 033
-- Exibicoes rapidas no telao: Pix de dizimo/oferta (QR gerado na hora a
-- partir da chave Pix da igreja, ver Igrejas\Core\PixEstatico) e uma
-- galeria de imagens que a igreja sobe e marca como favoritas, pra o
-- operador exibir em tela cheia com um clique (ex.: cartaz de um
-- evento, aviso especial).

ALTER TABLE projecao_estados
    MODIFY COLUMN modo ENUM('biblia', 'video', 'logo', 'blank', 'pix', 'imagem') NOT NULL DEFAULT 'blank',
    ADD COLUMN pix_categoria VARCHAR(20) NULL AFTER video_duracao,
    ADD COLUMN imagem_id INT UNSIGNED NULL AFTER pix_categoria;

CREATE TABLE IF NOT EXISTS projecao_imagens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_arquivo VARCHAR(190) NOT NULL,
    path VARCHAR(255) NOT NULL,
    favorita TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY projecao_imagens_favorita_index (favorita)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE projecao_estados
    ADD CONSTRAINT projecao_estados_imagem_id_foreign
        FOREIGN KEY (imagem_id) REFERENCES projecao_imagens (id) ON DELETE SET NULL;
