-- Produtos vendidos avulsamente (nao vinculados a um agendamento),
-- com controle simples de estoque. Uma venda registra baixa no
-- estoque e gera um lancamento financeiro de receita (ver
-- Barbearias\Controllers\ProdutoController::vender).
CREATE TABLE IF NOT EXISTS produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    estoque_atual INT NOT NULL DEFAULT 0,
    estoque_minimo INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY produtos_barbearia_id_index (barbearia_id),
    CONSTRAINT produtos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Liga um lancamento financeiro de receita a uma venda de produto
-- (quantidade vendida), pro mesmo padrao ja usado com agendamento_id
-- (pagamento de atendimento).
ALTER TABLE financeiro_lancamentos
    ADD COLUMN produto_id INT UNSIGNED NULL AFTER agendamento_id,
    ADD COLUMN quantidade INT UNSIGNED NULL AFTER produto_id,
    ADD CONSTRAINT financeiro_lancamentos_produto_id_foreign
        FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE SET NULL;
