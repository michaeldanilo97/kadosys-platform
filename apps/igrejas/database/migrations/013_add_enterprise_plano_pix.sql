-- KADOSYS Igrejas - Migracao 013
-- "plataforma_provisionamentos" e "plataforma_faturas" foram criadas
-- (migracoes 011 e 012) com plano ENUM('essencial', 'premium') - faltou
-- "enterprise", que ate entao so era vendido "sob consulta" (sem
-- cadastro publico nem cobranca automatica, entao nunca precisava
-- passar por essas tabelas). Agora que o antigo "Enterprise" virou um
-- plano autoatendido (rebatizado "Premium" na pagina de vendas, mas o
-- valor gravado no banco continua "enterprise" - ver Plano::LABELS),
-- essas duas tabelas tambem precisam aceitar esse valor, senao o
-- cadastro/a fatura Pix desse plano falha.
--
-- "plataforma_tenants" ja foi criada com os 3 valores desde a migracao
-- 011 e nao precisa de ajuste.

ALTER TABLE plataforma_provisionamentos
    MODIFY COLUMN plano ENUM('essencial', 'premium', 'enterprise') NOT NULL;

ALTER TABLE plataforma_faturas
    MODIFY COLUMN plano ENUM('essencial', 'premium', 'enterprise') NOT NULL;
