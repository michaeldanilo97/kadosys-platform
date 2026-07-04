-- KADOSYS Igrejas - Migracao 026
-- Endereco da igreja (capturado no cadastro publico do dominio
-- principal, ver CadastroController/migracao 025) copiado pro banco
-- individual de cada igreja por Provisionador::inserirDadosIniciais()
-- no momento do provisionamento.

ALTER TABLE configuracoes_igreja
    ADD COLUMN cep VARCHAR(9) NULL AFTER plano,
    ADD COLUMN endereco VARCHAR(190) NULL AFTER cep,
    ADD COLUMN numero VARCHAR(20) NULL AFTER endereco,
    ADD COLUMN cidade VARCHAR(100) NULL AFTER numero,
    ADD COLUMN estado CHAR(2) NULL AFTER cidade;
