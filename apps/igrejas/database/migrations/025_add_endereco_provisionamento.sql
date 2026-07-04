-- KADOSYS Igrejas - Migracao 025
-- Endereco (com CEP) no cadastro publico de igreja nova, no dominio
-- principal (ver CadastroController) - antes so pedia documento
-- (CPF/CNPJ) e razao social, sem endereco nenhum.
--
-- So em plataforma_provisionamentos (a tentativa de cadastro) - o
-- endereco de verdade da igreja, usado depois de provisionada, vive em
-- configuracoes_igreja (banco individual de cada igreja, ver migracao
-- 026_add_endereco_configuracoes_igreja.sql), copiado de la pelo
-- Provisionador::inserirDadosIniciais() no momento do provisionamento.
--
-- IMPORTANTE: esta migracao roda SO na instalacao central (a que
-- recebe os cadastros publicos), assim como 011/012/013/014/021 - nao
-- faz parte do database/install.sql.

ALTER TABLE plataforma_provisionamentos
    ADD COLUMN cep VARCHAR(9) NULL AFTER razao_social,
    ADD COLUMN endereco VARCHAR(190) NULL AFTER cep,
    ADD COLUMN numero VARCHAR(20) NULL AFTER endereco,
    ADD COLUMN cidade VARCHAR(100) NULL AFTER numero,
    ADD COLUMN estado CHAR(2) NULL AFTER cidade;
