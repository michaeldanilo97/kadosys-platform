-- Ajuste 188: Galeria de Memorias - mural de fotos dos momentos
-- marcantes da igreja (cultos especiais, batismos, eventos, confraternizacoes),
-- com legenda e data do acontecimento. Publica em /galeria (sem login,
-- mesmo espirito do quadro de avisos em /avisos), pra compartilhar com
-- a congregacao.

CREATE TABLE IF NOT EXISTS galeria_memorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    legenda VARCHAR(500) NULL,
    data_registro DATE NULL,
    foto_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY galeria_memorias_data_registro_index (data_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
