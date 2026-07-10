-- KADOSYS Igrejas - Migracao 037
-- Escolhe quem ve cada aviso do dono da plataforma: so admins, so
-- membros comuns ou todo mundo - ver Igrejas\Models\PlataformaAviso.
-- Default 'todos' preserva o comportamento de avisos ja publicados
-- antes desta coluna existir (todo mundo continua vendo os antigos).
--
-- Assim como 011/012/013/014/021/025/027/028/036, esta migracao roda SO
-- na instalacao central - nao faz parte de database/install.sql.

ALTER TABLE plataforma_avisos
    ADD COLUMN publico_alvo ENUM('admins', 'membros', 'todos') NOT NULL DEFAULT 'todos' AFTER ativo;
