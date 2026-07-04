-- KADOSYS Igrejas - Migracao 027
-- Mapeamento central de preapproval_id (assinatura via cartao) pro
-- tenant dono dela - resolve uma lacuna real: quando uma igreja de
-- SUBDOMINIO ja existente troca de plano por cartao (Checkout Pro/
-- preapproval, ver AssinaturaController::iniciar()), o registro fica
-- gravado na tabela "assinaturas" de dentro do banco isolado DAQUELA
-- igreja (Database::connection() no momento da troca ja esta
-- apontando pra la, via TenantResolver). O webhook do Mercado Pago,
-- porem, sempre chega pela URL central (nunca por um subdominio
-- especifico), entao Database::connection() dentro dele nunca e o
-- banco daquele tenant - a busca por
-- Assinatura::buscarPorPreapprovalId() la dentro nunca vai encontrar
-- nada, e o plano daquela igreja nunca era atualizado automaticamente.
--
-- "plataforma_assinaturas" e essa ponte: guarda so o necessario
-- (tenant_id + plano + preapproval_id + status) pra que o webhook,
-- rodando na instalacao central, consiga achar o tenant certo e
-- conectar direto no banco dele pra aplicar a troca de plano - mesmo
-- padrao ja usado por plataforma_faturas/Tenant::atualizarPlano() e
-- AssinaturaController::atualizarPlanoNoBancoDoTenant() pro Pix.
--
-- So se aplica a assinaturas de tenants de subdominio (ver
-- AssinaturaController::iniciar()); a instalacao original (sem
-- TenantResolver, "igreja basica") continua resolvendo pela propria
-- tabela "assinaturas" local, sem precisar dessa ponte.
--
-- Assim como 011/012/013/014/021/025, esta migracao roda SO na
-- instalacao central - nao faz parte de database/install.sql.

CREATE TABLE IF NOT EXISTS plataforma_assinaturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL,
    mp_preapproval_id VARCHAR(64) NOT NULL,
    status ENUM('pendente', 'autorizada', 'pausada', 'cancelada') NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_plataforma_assinatura_preapproval_id (mp_preapproval_id),
    CONSTRAINT fk_plataforma_assinatura_tenant
        FOREIGN KEY (tenant_id) REFERENCES plataforma_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
