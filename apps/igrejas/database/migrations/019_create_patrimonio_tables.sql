-- KADOSYS Igrejas - Migracao 019
-- Tabela do modulo Patrimonio: bens, imoveis e equipamentos da igreja.

CREATE TABLE IF NOT EXISTS patrimonio_bens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    categoria ENUM('imovel', 'veiculo', 'equipamento', 'mobiliario', 'eletronico', 'outro') NOT NULL DEFAULT 'outro',
    numero_patrimonio VARCHAR(50) NULL,
    descricao TEXT NULL,
    valor_estimado DECIMAL(10,2) NULL,
    data_aquisicao DATE NULL,
    local VARCHAR(150) NULL,
    status ENUM('ativo', 'manutencao', 'baixado') NOT NULL DEFAULT 'ativo',
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY patrimonio_bens_categoria_index (categoria),
    KEY patrimonio_bens_status_index (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
