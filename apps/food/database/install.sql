-- KADOSYS Food - Schema inicial (Fase 1)
-- RODAR UMA UNICA VEZ no banco kadosys1_food (banco UNICO e
-- compartilhado entre todos os restaurantes - diferente do KADOSYS
-- Igrejas, aqui NAO ha um banco por cliente). Toda tabela de negocio
-- (exceto a propria `restaurantes`) tem uma coluna restaurante_id que
-- isola os dados de cada restaurante - todo Model PRECISA filtrar por
-- ela em toda query, ja que o banco nao faz esse isolamento sozinho.
--
-- Esta Fase 1 traz so o esqueleto (tenant + billing + login). As
-- tabelas de catalogo/estoque/pedidos/financeiro entram nas fases
-- seguintes, cada uma com sua propria migration.

-- "status" comeca 'pendente' pra quem escolhe Pix/cartao (so vira
-- 'ativo' quando o webhook do Mercado Pago confirma o pagamento) e ja
-- nasce 'ativo' pra quem escolhe trial (sem cobranca nenhuma nos
-- primeiros dias, ver Food\Models\Plano::TRIAL_DIAS). Diferente do
-- KADOSYS Igrejas, aqui NAO ha banco/subdominio pra provisionar -
-- "ativar" e so um UPDATE nesta mesma linha, sem infraestrutura
-- nenhuma de por meio.
CREATE TABLE IF NOT EXISTS restaurantes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    telefone VARCHAR(20) NULL,
    documento_tipo ENUM('cpf', 'cnpj') NOT NULL DEFAULT 'cpf',
    documento VARCHAR(14) NOT NULL DEFAULT '',
    razao_social VARCHAR(190) NULL,
    logo_path VARCHAR(255) NULL,
    cor_primaria VARCHAR(7) NULL,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL DEFAULT 'essencial',
    metodo_pagamento ENUM('cartao', 'pix', 'trial') NOT NULL DEFAULT 'trial',
    mp_preapproval_id VARCHAR(64) NULL,
    trial_expira_em DATETIME NULL,
    proximo_vencimento DATE NULL,
    plano_agendado VARCHAR(20) NULL,
    ultimo_acesso_em DATETIME NULL,
    status ENUM('pendente', 'ativo', 'suspenso') NOT NULL DEFAULT 'pendente',
    cancelado_em DATETIME NULL,
    pix_chave VARCHAR(140) NULL,
    pix_nome_beneficiario VARCHAR(25) NULL,
    pix_cidade VARCHAR(15) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY restaurantes_slug_unique (slug),
    KEY restaurantes_documento_index (documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cobranca Pix avulsa - cobre tanto o PRIMEIRO pagamento (cadastro
-- novo) quanto cada renovacao mensal seguinte (gerada por
-- cron/gerar_faturas_pix.php), sem distincao entre as duas. Cartao nao
-- gera linha aqui: a cobranca recorrente e feita pelo proprio Mercado
-- Pago via preapproval (ver restaurantes.mp_preapproval_id).
CREATE TABLE IF NOT EXISTS restaurante_faturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL,
    tipo ENUM('renovacao', 'upgrade_proporcional') NOT NULL DEFAULT 'renovacao',
    valor DECIMAL(10, 2) NOT NULL,
    mp_payment_id VARCHAR(64) NULL,
    pix_qr_code TEXT NULL,
    pix_qr_code_base64 MEDIUMTEXT NULL,
    status ENUM('pendente', 'paga', 'expirada', 'cancelada') NOT NULL DEFAULT 'pendente',
    vencimento DATETIME NOT NULL,
    pago_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY restaurante_faturas_mp_payment_id_unique (mp_payment_id),
    KEY restaurante_faturas_restaurante_id_index (restaurante_id),
    CONSTRAINT restaurante_faturas_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users.email e unico GLOBALMENTE (nao por restaurante) - cada conta
-- de acesso pertence a exatamente um restaurante, e o login resolve
-- direto pra qual sem precisar de uma etapa de selecao.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'usuario') NOT NULL DEFAULT 'admin',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY users_email_unique (email),
    KEY users_restaurante_id_index (restaurante_id),
    CONSTRAINT users_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recuperacao de senha ("esqueci minha senha", ver Food\Controllers\AuthController).
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY password_resets_email_index (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KADOSYS Food - Fase 2 (Categorias, Ingredientes, Fornecedores)
-- Ver database/migrations/001_categorias_ingredientes_fornecedores.sql
-- para detalhes/comentarios de cada coluna.

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

-- KADOSYS Food - Fase 3 (Produtos, Ficha Tecnica, Custeio)
-- Ver database/migrations/002_produtos_ficha_tecnica_custeio.sql para
-- detalhes/comentarios de cada coluna.

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

-- KADOSYS Food - Fase 4 (Estoque, Compras)
-- Ver database/migrations/003_estoque_compras.sql para detalhes/comentarios
-- de cada coluna.

CREATE TABLE IF NOT EXISTS estoque_movimentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    ingrediente_id INT UNSIGNED NOT NULL,
    tipo ENUM('entrada', 'saida', 'inventario', 'perda') NOT NULL,
    quantidade DECIMAL(10, 3) NOT NULL,
    motivo VARCHAR(255) NULL,
    referencia_tipo VARCHAR(30) NULL,
    referencia_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY estoque_movimentos_restaurante_id_index (restaurante_id),
    KEY estoque_movimentos_ingrediente_id_index (ingrediente_id),
    CONSTRAINT estoque_movimentos_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE,
    CONSTRAINT estoque_movimentos_ingrediente_id_foreign
        FOREIGN KEY (ingrediente_id) REFERENCES ingredientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS compras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    fornecedor_id INT UNSIGNED NULL,
    data_compra DATE NOT NULL,
    frete DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    observacao TEXT NULL,
    valor_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY compras_restaurante_id_index (restaurante_id),
    KEY compras_fornecedor_id_index (fornecedor_id),
    CONSTRAINT compras_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE,
    CONSTRAINT compras_fornecedor_id_foreign
        FOREIGN KEY (fornecedor_id) REFERENCES fornecedores (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS compra_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    compra_id INT UNSIGNED NOT NULL,
    ingrediente_id INT UNSIGNED NOT NULL,
    quantidade DECIMAL(10, 3) NOT NULL,
    unidade VARCHAR(20) NOT NULL,
    preco_unitario DECIMAL(10, 4) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    validade DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY compra_itens_compra_id_index (compra_id),
    KEY compra_itens_ingrediente_id_index (ingrediente_id),
    CONSTRAINT compra_itens_compra_id_foreign
        FOREIGN KEY (compra_id) REFERENCES compras (id) ON DELETE CASCADE,
    CONSTRAINT compra_itens_ingrediente_id_foreign
        FOREIGN KEY (ingrediente_id) REFERENCES ingredientes (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
