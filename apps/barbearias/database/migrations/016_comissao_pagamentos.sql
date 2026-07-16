-- Registro de pagamento de comissao a um profissional - gera uma
-- despesa no caixa aberto (financeiro_lancamentos) e guarda o
-- comprovante anexado, alem do periodo exato pago (pra nao deixar
-- pagar a mesma comissao duas vezes, ver
-- Barbearias\Models\ComissaoPagamento::porPeriodo).
CREATE TABLE IF NOT EXISTS comissao_pagamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NOT NULL,
    financeiro_lancamento_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fim DATE NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    comprovante_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY comissao_pagamentos_barbearia_id_index (barbearia_id),
    KEY comissao_pagamentos_profissional_periodo_index (profissional_id, periodo_inicio, periodo_fim),
    CONSTRAINT comissao_pagamentos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT comissao_pagamentos_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE,
    CONSTRAINT comissao_pagamentos_lancamento_id_foreign
        FOREIGN KEY (financeiro_lancamento_id) REFERENCES financeiro_lancamentos (id) ON DELETE CASCADE,
    CONSTRAINT comissao_pagamentos_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
