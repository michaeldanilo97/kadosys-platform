-- KADOSYS Igrejas - Migracao 014
-- CPF/CNPJ obrigatorio no cadastro (com razao social pra pessoa
-- juridica) e teste gratis de 7 dias.
--
-- O documento fica gravado tanto em plataforma_provisionamentos (a
-- tentativa de cadastro) quanto em plataforma_tenants (o registro
-- permanente da igreja) - e nesse ultimo que a checagem de "esse
-- documento ja usou o teste gratis antes?" e feita (ver
-- Tenant::documentoJaUsouTrial), entao precisa estar la de forma
-- permanente mesmo depois do provisionamento terminar.
--
-- "trial" vira um terceiro valor possivel de metodo_pagamento (antes
-- so cartao/pix) - representa uma igreja que ainda nao escolheu forma
-- de pagamento de verdade, usando o sistema gratis por
-- trial_expira_em (so preenchido nesse caso).

ALTER TABLE plataforma_provisionamentos
    ADD COLUMN documento_tipo ENUM('cpf', 'cnpj') NOT NULL DEFAULT 'cpf' AFTER admin_email,
    ADD COLUMN documento VARCHAR(14) NOT NULL DEFAULT '' AFTER documento_tipo,
    ADD COLUMN razao_social VARCHAR(190) NULL AFTER documento,
    MODIFY COLUMN metodo_pagamento ENUM('cartao', 'pix', 'trial') NOT NULL DEFAULT 'cartao';

ALTER TABLE plataforma_tenants
    ADD COLUMN documento_tipo ENUM('cpf', 'cnpj') NOT NULL DEFAULT 'cpf' AFTER nome_igreja,
    ADD COLUMN documento VARCHAR(14) NOT NULL DEFAULT '' AFTER documento_tipo,
    ADD COLUMN razao_social VARCHAR(190) NULL AFTER documento,
    ADD COLUMN trial_expira_em DATETIME NULL AFTER metodo_pagamento,
    MODIFY COLUMN metodo_pagamento ENUM('cartao', 'pix', 'trial') NOT NULL DEFAULT 'cartao',
    ADD INDEX idx_tenant_documento (documento);
