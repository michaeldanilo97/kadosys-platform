-- KADOSYS Igrejas - Migracao 021
-- Avisos do dono da plataforma (KADOSYS) para todas as igrejas
-- cadastradas - ex.: manutencao programada, novo recurso disponivel.
-- Publicado pelo painel da plataforma (o mesmo onde as igrejas sao
-- excluidas, ver PlataformaController) e mostrado no sino de
-- notificacoes do painel de CADA igreja.
--
-- IMPORTANTE: assim como plataforma_tenants/plataforma_provisionamentos,
-- esta migracao roda SO nesta instalacao central - de proposito NAO faz
-- parte do database/install.sql.

CREATE TABLE IF NOT EXISTS plataforma_avisos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mensagem TEXT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY plataforma_avisos_ativo_index (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
