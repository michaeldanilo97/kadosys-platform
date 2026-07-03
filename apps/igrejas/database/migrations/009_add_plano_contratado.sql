-- KADOSYS Igrejas - Migracao 009
-- Plano contratado desta instalacao (Essencial/Premium/Enterprise), usado
-- para liberar ou bloquear modulos do menu de acordo com o plano anunciado
-- na pagina de vendas (ver resources/views/landing/home.php, secao
-- #planos). Instalacao single-tenant: um unico valor vale pra igreja
-- inteira, guardado na linha unica de configuracoes_igreja (id = 1).

ALTER TABLE configuracoes_igreja
    ADD COLUMN plano ENUM('essencial', 'premium', 'enterprise') NOT NULL DEFAULT 'essencial' AFTER logo_path;
