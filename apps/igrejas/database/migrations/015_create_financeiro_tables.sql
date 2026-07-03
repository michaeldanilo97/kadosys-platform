-- KADOSYS Igrejas - Migracao 015
-- Tabelas do modulo Financeiro: categorias (dizimo, oferta, despesas
-- etc - cada categoria tem um tipo fixo entrada/saida) e os
-- lancamentos em si, com valor, forma de pagamento, data e vinculo
-- opcional com um membro (pra registrar de quem foi um dizimo/oferta
-- especifico, quando a igreja quiser esse controle).

CREATE TABLE IF NOT EXISTS financeiro_categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    status ENUM('ativa', 'inativa') NOT NULL DEFAULT 'ativa',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY financeiro_categorias_tipo_index (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financeiro_lancamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('entrada', 'saida') NOT NULL,
    categoria_id INT UNSIGNED NULL,
    membro_id INT UNSIGNED NULL,
    descricao VARCHAR(190) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('dinheiro', 'pix', 'cartao', 'transferencia', 'boleto', 'outro') NOT NULL DEFAULT 'dinheiro',
    data_lancamento DATE NOT NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY financeiro_lancamentos_tipo_index (tipo),
    KEY financeiro_lancamentos_data_index (data_lancamento),
    CONSTRAINT financeiro_lancamentos_categoria_id_foreign
        FOREIGN KEY (categoria_id) REFERENCES financeiro_categorias (id) ON DELETE SET NULL,
    CONSTRAINT financeiro_lancamentos_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO financeiro_categorias (nome, tipo) VALUES
    ('Dizimo', 'entrada'),
    ('Oferta', 'entrada'),
    ('Oferta especial / Missoes', 'entrada'),
    ('Doacao', 'entrada'),
    ('Aluguel e contas do templo', 'saida'),
    ('Manutencao', 'saida'),
    ('Materiais e suprimentos', 'saida'),
    ('Salarios e honorarios', 'saida'),
    ('Eventos', 'saida'),
    ('Missoes e acoes sociais', 'saida'),
    ('Outros', 'saida');
