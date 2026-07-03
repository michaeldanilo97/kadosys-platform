-- KADOSYS Igrejas - Migracao 012
-- Cobranca recorrente via Pix avulso (alem do cartao via preapproval, ja
-- existente) - ver Igrejas\Core\MercadoPagoClient::criarPagamentoPix().
--
-- O PRIMEIRO pagamento (que cria a igreja) fica registrado direto em
-- plataforma_provisionamentos (colunas pix_* novas abaixo) - nao precisa
-- de tenant_id ainda, porque o tenant so existe depois do provisionamento.
--
-- Cada RENOVACAO mensal seguinte (so se aplica a quem paga por Pix - quem
-- paga por cartao renova sozinho, sem gerar linha aqui) fica em
-- "plataforma_faturas", gerada automaticamente por
-- cron/gerar_faturas_pix.php.
--
-- Assim como 011, este arquivo roda SO na instalacao central - nao faz
-- parte de database/install.sql (essas tabelas nao tem nada a ver com o
-- banco individual de cada igreja).

ALTER TABLE plataforma_provisionamentos
    ADD COLUMN metodo_pagamento ENUM('cartao', 'pix') NOT NULL DEFAULT 'cartao' AFTER plano,
    ADD COLUMN mp_payment_id VARCHAR(64) NULL AFTER mp_preapproval_id,
    ADD COLUMN pix_qr_code TEXT NULL,
    ADD COLUMN pix_qr_code_base64 MEDIUMTEXT NULL,
    ADD COLUMN pix_vencimento DATETIME NULL,
    ADD UNIQUE KEY uq_provisionamento_mp_payment_id (mp_payment_id);

ALTER TABLE plataforma_tenants
    ADD COLUMN metodo_pagamento ENUM('cartao', 'pix') NOT NULL DEFAULT 'cartao' AFTER plano;

CREATE TABLE IF NOT EXISTS plataforma_faturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    plano ENUM('essencial', 'premium') NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    mp_payment_id VARCHAR(64) NULL,
    pix_qr_code TEXT NULL,
    pix_qr_code_base64 MEDIUMTEXT NULL,
    status ENUM('pendente', 'paga', 'expirada', 'cancelada') NOT NULL DEFAULT 'pendente',
    vencimento DATETIME NOT NULL,
    pago_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fatura_mp_payment_id (mp_payment_id),
    CONSTRAINT fk_fatura_tenant
        FOREIGN KEY (tenant_id) REFERENCES plataforma_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
