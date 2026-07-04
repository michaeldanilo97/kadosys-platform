-- KADOSYS Igrejas - Migracao 023
-- Liga/desliga o auto-cadastro publico de membros (ver
-- MembroPublicoController e ConfiguracaoIgreja::atualizarCadastroMembros()).
-- Desligado por padrao: a igreja precisa optar por habilitar antes de
-- qualquer visitante conseguir se cadastrar sozinho como membro.

ALTER TABLE configuracoes_igreja
    ADD COLUMN cadastro_membros_habilitado TINYINT(1) NOT NULL DEFAULT 0 AFTER plano;
