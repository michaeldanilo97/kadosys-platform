-- KADOSYS Food - Migration 002
-- Rodar uma unica vez em restaurantes ja instalados antes desta
-- migration (instalacoes novas ja recebem isso direto pelo install.sql).

-- Produto vendido (o prato/item do cardapio). Precos por canal ficam
-- todos nesta tabela mesmo (nenhum e obrigatorio alem de preco_balcao)
-- - "preco_ideal_*" e "custo_total"/"markup"/"margem_percentual"/"lucro"
-- sao CACHE calculado por Food\Core\Custeio (ver
-- Produto::recalcularCusto()), nunca editados diretamente pelo usuario.
-- Os "*_override" sao por-produto e sobrescrevem o valor global
-- equivalente de custeio_config quando preenchidos (NULL = usa o
-- padrao da loja).
CREATE TABLE IF NOT EXISTS produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED NULL,
    codigo VARCHAR(60) NULL,
    codigo_barras VARCHAR(60) NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    foto_path VARCHAR(255) NULL,
    tags VARCHAR(255) NULL,
    observacoes TEXT NULL,
    tempo_preparo_min SMALLINT UNSIGNED NULL,
    peso_g DECIMAL(10, 2) NULL,
    rendimento SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('ativo', 'pausado', 'inativo') NOT NULL DEFAULT 'ativo',
    preco_balcao DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    preco_whatsapp DECIMAL(10, 2) NULL,
    preco_ifood DECIMAL(10, 2) NULL,
    preco_promocao DECIMAL(10, 2) NULL,
    preco_delivery_proprio DECIMAL(10, 2) NULL,
    custo_energia_override DECIMAL(10, 4) NULL,
    custo_gas_override DECIMAL(10, 4) NULL,
    custo_agua_override DECIMAL(10, 4) NULL,
    custo_embalagem_override DECIMAL(10, 4) NULL,
    custo_etiqueta_override DECIMAL(10, 4) NULL,
    custo_mao_obra_override DECIMAL(10, 4) NULL,
    custo_taxa_operacional_override DECIMAL(10, 4) NULL,
    custo_desperdicio_override DECIMAL(10, 4) NULL,
    custo_total DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    markup DECIMAL(8, 4) NOT NULL DEFAULT 0.0000,
    margem_percentual DECIMAL(6, 2) NOT NULL DEFAULT 0.00,
    lucro DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    preco_ideal_balcao DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    preco_ideal_whatsapp DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    preco_ideal_ifood DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    preco_ideal_delivery DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY produtos_restaurante_id_index (restaurante_id),
    KEY produtos_categoria_id_index (categoria_id),
    CONSTRAINT produtos_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE,
    CONSTRAINT produtos_categoria_id_foreign
        FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ficha tecnica (receita) de um produto: um item por ingrediente usado.
-- "unidade" e sempre a mesma unidade cadastrada no ingrediente (nao da
-- pra escolher outra aqui) - evita ter que construir um conversor de
-- unidades (kg->g, l->ml etc) que ninguem pediu; ela fica salva junto
-- so pra a tela de ficha tecnica não depender de outro JOIN pra exibir.
-- "perda_percentual" cobre desperdicio proprio do preparo (casca, apara,
-- evaporacao) - a quantidade efetivamente custeada e
-- quantidade * (1 + perda_percentual / 100).
CREATE TABLE IF NOT EXISTS ficha_tecnica_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    produto_id INT UNSIGNED NOT NULL,
    ingrediente_id INT UNSIGNED NOT NULL,
    quantidade DECIMAL(10, 3) NOT NULL,
    unidade VARCHAR(20) NOT NULL,
    perda_percentual DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ficha_tecnica_itens_produto_id_index (produto_id),
    KEY ficha_tecnica_itens_ingrediente_id_index (ingrediente_id),
    CONSTRAINT ficha_tecnica_itens_produto_id_foreign
        FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE CASCADE,
    CONSTRAINT ficha_tecnica_itens_ingrediente_id_foreign
        FOREIGN KEY (ingrediente_id) REFERENCES ingredientes (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuracao de custeio: 1 linha por restaurante, com os valores
-- globais de overhead (custo estimado por UNIDADE produzida - nao por
-- receita inteira) que valem pra todo produto que nao tiver um
-- override proprio, alem da margem desejada padrao e das taxas usadas
-- pra "engordar" o preco ideal de iFood/pagamento online de forma que o
-- valor liquido recebido (depois da taxa) ainda bata a margem
-- desejada. Criada com valores padrao na primeira vez que a tela de
-- Produtos precisar dela (ver CusteioConfig::obterOuCriar()) - a tela
-- de edicao desses valores fica pra Fase 7 (Precificacao Inteligente).
CREATE TABLE IF NOT EXISTS custeio_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    valor_energia_padrao DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    valor_gas_padrao DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    valor_agua_padrao DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    valor_embalagem_padrao DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    valor_etiqueta_padrao DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    valor_mao_obra_padrao DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    valor_taxa_operacional_padrao DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    valor_desperdicio_padrao DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
    margem_desejada_padrao DECIMAL(5, 2) NOT NULL DEFAULT 30.00,
    comissao_ifood_padrao DECIMAL(5, 2) NOT NULL DEFAULT 12.00,
    taxa_pagamento_online_padrao DECIMAL(5, 2) NOT NULL DEFAULT 3.49,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY custeio_config_restaurante_id_unique (restaurante_id),
    CONSTRAINT custeio_config_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
