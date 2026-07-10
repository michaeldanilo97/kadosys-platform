ALTER TABLE plataforma_tenants
    ADD COLUMN ultimo_acesso_em DATETIME NULL AFTER proximo_vencimento;
