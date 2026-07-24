-- KADOSYS Igrejas - Migracao 069
-- Ranking agregado de XP das criancas por igreja - alimenta o "ranking
-- entre igrejas" da Biblioteca Kids (ver Igrejas\Models\KidsRankingIgreja).
-- So guarda o TOTAL de XP por igreja, nunca dado de crianca nenhuma -
-- o ranking entre igrejas mostra so o nome da igreja, o ranking
-- crianca-por-crianca continua restrito a propria igreja
-- (KidsCrianca::rankingDaIgreja(), direto no banco de cada uma).
--
-- IMPORTANTE: assim como plataforma_tenants/plataforma_avisos, esta
-- migracao roda SO na instalacao central - de proposito NAO faz parte
-- do database/install.sql.

CREATE TABLE IF NOT EXISTS plataforma_kids_ranking (
    tenant_id INT UNSIGNED PRIMARY KEY,
    xp_total_kids INT UNSIGNED NOT NULL DEFAULT 0,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_kids_ranking_tenant
        FOREIGN KEY (tenant_id) REFERENCES plataforma_tenants (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
