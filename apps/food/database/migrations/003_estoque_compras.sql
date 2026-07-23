-- KADOSYS Food - Migration 003
-- Rodar uma unica vez em restaurantes ja instalados antes desta
-- migration (instalacoes novas ja recebem isso direto pelo install.sql).

-- Log auditavel de toda movimentacao de estoque. "quantidade" e sempre
-- um numero positivo (a direcao vem do "tipo") EXCETO em ajustes de
-- inventario, onde representa a contagem NOVA total apos o ajuste (nao
-- um delta) - contagem fisica substitui o numero anterior, entao faz
-- mais sentido guardar o valor final conferido do que a diferenca.
-- `ingrediente.estoque_atual` continua sendo o cache rapido consultado
-- no dia a dia; esta tabela existe so pra auditoria/historico.
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

-- Cabecalho de uma compra de ingredientes. "valor_total" e cache (soma
-- dos subtotais dos itens + frete), recalculado a cada item
-- adicionado - ver Compra::recalcularValorTotal(). Uma compra e um
-- registro APENDICE-SO nesta entrega: nao ha edicao/exclusao de itens
-- ja lancados (isso ja alterou estoque/preco de verdade, e desfazer
-- corretamente exigiria reverter ambos com seguranca mesmo que o
-- estoque ja tenha sido parcialmente consumido por vendas - fora de
-- escopo aqui, mesma simplificacao assumida pra vencimento/FEFO).
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

-- Item de uma compra. "unidade" e sempre copiada do ingrediente no
-- momento da compra (mesma logica de ficha_tecnica_itens - sem
-- conversor de unidades). "validade" e opcional e alimenta so um
-- alerta simples de "vencendo em N dias" (sem rastreamento de lote/FEFO
-- - ver comentario na tabela `compras`).
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
