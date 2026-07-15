-- Percentual de comissao que o profissional recebe sobre o valor dos
-- servicos que realiza. Usado pelo relatorio de fechamento em
-- /dashboard/comissoes (Barbearias\Controllers\ComissaoController).
ALTER TABLE profissionais
    ADD COLUMN percentual_comissao DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER horario_fim;
