-- KADOSYS Food - Migration 001
-- Rodar uma unica vez em restaurantes ja instalados antes desta
-- migration (instalacoes novas ja recebem isso direto pelo install.sql).

-- Categorias de PRODUTO (Doces, Bolos, Salgados, etc - usadas a partir
-- da Fase 3 em produtos.categoria_id). Cada restaurante recebe o seed
-- padrao automaticamente no cadastro (ver Food\Models\Categoria::seedPadrao(),
-- chamado por CadastroController::enviar()), mas a lista e editavel -
-- nao e um ENUM fixo.
CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    nome VARCHAR(60) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY categorias_restaurante_id_index (restaurante_id),
    CONSTRAINT categorias_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fornecedores de ingrediente. "forma_pagamento" e texto livre (nao
-- ENUM) porque prazos combinados com fornecedor variam demais (a vista,
-- boleto 30/60, etc) pra travar num conjunto fixo de opcoes.
CREATE TABLE IF NOT EXISTS fornecedores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    telefone VARCHAR(20) NULL,
    whatsapp VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    contato VARCHAR(150) NULL,
    prazo_dias INT UNSIGNED NULL,
    forma_pagamento VARCHAR(60) NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY fornecedores_restaurante_id_index (restaurante_id),
    CONSTRAINT fornecedores_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ingredientes (a base da Ficha Tecnica, ver Fase 3). "categoria" aqui
-- e texto livre do proprio usuario (ex.: "Laticinios", "Embalagens") -
-- DIFERENTE da tabela `categorias` acima, que e especificamente pra
-- categorizar PRODUTOS (o prato/item vendido), nao ingredientes.
-- estoque_atual/estoque_minimo em DECIMAL (nao INT) porque ingrediente
-- se compra e se usa fracionado (ex.: 2.5kg de farinha).
-- preco_atual e atualizado a cada Compra (ver Fase 4) e dispara o
-- recalculo de custo dos produtos que usam esse ingrediente na ficha
-- tecnica (ver Fase 3, Food\Core\Custeio).
CREATE TABLE IF NOT EXISTS ingredientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    categoria VARCHAR(60) NULL,
    fornecedor_id INT UNSIGNED NULL,
    codigo VARCHAR(60) NULL,
    unidade VARCHAR(20) NOT NULL DEFAULT 'un',
    preco_atual DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    preco_medio DECIMAL(10, 4) NULL,
    ultima_compra_em DATE NULL,
    estoque_atual DECIMAL(10, 3) NOT NULL DEFAULT 0.000,
    estoque_minimo DECIMAL(10, 3) NOT NULL DEFAULT 0.000,
    localizacao VARCHAR(100) NULL,
    observacao TEXT NULL,
    foto_path VARCHAR(255) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ingredientes_restaurante_id_index (restaurante_id),
    KEY ingredientes_fornecedor_id_index (fornecedor_id),
    CONSTRAINT ingredientes_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE,
    CONSTRAINT ingredientes_fornecedor_id_foreign
        FOREIGN KEY (fornecedor_id) REFERENCES fornecedores (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
