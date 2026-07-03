-- KADOSYS Igrejas - Migracao 010
-- Assinaturas recorrentes via Mercado Pago (Checkout Pro + Assinaturas/
-- preapproval), ligadas ao plano contratado (ver Igrejas\Models\Plano).
--
-- "assinaturas" guarda o historico de tentativas de assinatura (uma
-- linha nova a cada vez que o admin clica em "Assinar", mesmo que ainda
-- nao tenha sido paga) - nao e uma tabela singleton como
-- configuracoes_igreja, porque a igreja pode trocar/tentar de novo.
--
-- "assinatura_eventos" guarda um log bruto de cada notificacao recebida
-- do Mercado Pago (e falhas de validacao), pra diagnosticar problemas de
-- pagamento sem depender de log de arquivo em hospedagem compartilhada.

CREATE TABLE IF NOT EXISTS assinaturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL,
    mp_preapproval_id VARCHAR(64) NULL,
    mp_payer_email VARCHAR(190) NULL,
    status ENUM('pendente', 'autorizada', 'pausada', 'cancelada') NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mp_preapproval_id (mp_preapproval_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assinatura_eventos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assinatura_id INT UNSIGNED NULL,
    tipo VARCHAR(40) NOT NULL,
    payload TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assinatura_eventos_assinatura
        FOREIGN KEY (assinatura_id) REFERENCES assinaturas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
