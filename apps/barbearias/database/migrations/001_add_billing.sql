-- KADOSYS Barbearias - Migracao 001
-- Cobranca automatica (Pix/cartao via Mercado Pago) + trial de 5 dias.
--
-- So precisa rodar esta migracao se `apps/barbearias/database/install.sql`
-- JA foi executado antes no banco `kadosys1_barbearias` (ele so cria
-- tabela com CREATE TABLE IF NOT EXISTS, entao nao adiciona coluna em
-- tabela que ja existe). Se o install.sql ainda nao rodou nenhuma vez,
-- NAO precisa desta migracao - a versao atual do install.sql ja inclui
-- tudo isso desde o inicio.

ALTER TABLE barbearias
    ADD COLUMN documento_tipo ENUM('cpf', 'cnpj') NOT NULL DEFAULT 'cpf' AFTER telefone,
    ADD COLUMN documento VARCHAR(14) NOT NULL DEFAULT '' AFTER documento_tipo,
    ADD COLUMN razao_social VARCHAR(190) NULL AFTER documento,
    ADD COLUMN plano ENUM('essencial', 'premium', 'enterprise') NOT NULL DEFAULT 'essencial' AFTER razao_social,
    ADD COLUMN metodo_pagamento ENUM('cartao', 'pix', 'trial') NOT NULL DEFAULT 'trial' AFTER plano,
    ADD COLUMN mp_preapproval_id VARCHAR(64) NULL AFTER metodo_pagamento,
    ADD COLUMN trial_expira_em DATETIME NULL AFTER mp_preapproval_id,
    ADD COLUMN proximo_vencimento DATE NULL AFTER trial_expira_em,
    ADD COLUMN plano_agendado VARCHAR(20) NULL AFTER proximo_vencimento,
    ADD COLUMN ultimo_acesso_em DATETIME NULL AFTER plano_agendado,
    MODIFY COLUMN status ENUM('pendente', 'ativo', 'suspenso') NOT NULL DEFAULT 'pendente',
    ADD INDEX barbearias_documento_index (documento);

CREATE TABLE IF NOT EXISTS barbearia_faturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
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
    UNIQUE KEY barbearia_faturas_mp_payment_id_unique (mp_payment_id),
    KEY barbearia_faturas_barbearia_id_index (barbearia_id),
    CONSTRAINT barbearia_faturas_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
