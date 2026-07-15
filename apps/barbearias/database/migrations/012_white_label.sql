-- White-label basico: logo e cor de destaque por barbearia, aplicados
-- no dashboard e na pagina publica de agendamento (ver
-- Barbearias\Controllers\ConfiguracaoController).
ALTER TABLE barbearias
    ADD COLUMN logo_path VARCHAR(255) NULL AFTER razao_social,
    ADD COLUMN cor_primaria VARCHAR(7) NULL AFTER logo_path;
