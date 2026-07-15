-- KADOSYS Barbearias - Migracao 002
-- Perfil completo do profissional (e-mail, foto, dias/horario de
-- atendimento) - necessario pro agendamento publico calcular horarios
-- disponiveis.
--
-- So precisa rodar esta migracao se `install.sql` JA foi executado
-- antes no banco `kadosys1_barbearias`. Se ainda nao rodou nenhuma
-- vez, NAO precisa desta migracao - a versao atual do install.sql ja
-- inclui tudo isso desde o inicio.

ALTER TABLE profissionais
    ADD COLUMN email VARCHAR(150) NULL AFTER especialidade,
    ADD COLUMN foto_path VARCHAR(255) NULL AFTER telefone,
    ADD COLUMN dias_atendimento VARCHAR(20) NULL AFTER foto_path,
    ADD COLUMN horario_inicio TIME NULL AFTER dias_atendimento,
    ADD COLUMN horario_fim TIME NULL AFTER horario_inicio;
