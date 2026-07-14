-- KADOSYS Igrejas - Migracao 046
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Tela de perfil do membro (clicar no nome, na listagem) precisa de
-- mais campos que o cadastro simples de hoje nao tinha: documento
-- (CPF/RG/naturalidade) e endereco estruturado (logradouro, numero,
-- complemento, bairro), ja que ate aqui "endereco" era um unico campo
-- de texto livre. O campo endereco existente fica como estava (nao
-- migra dado antigo pra logradouro automaticamente - member antigo
-- so preenche os campos novos se/quando editar o proprio endereco de
-- novo).

ALTER TABLE membros
    ADD COLUMN cpf VARCHAR(14) NULL AFTER estado_civil,
    ADD COLUMN rg VARCHAR(20) NULL AFTER cpf,
    ADD COLUMN naturalidade VARCHAR(100) NULL AFTER rg,
    ADD COLUMN logradouro VARCHAR(150) NULL AFTER cep,
    ADD COLUMN numero VARCHAR(20) NULL AFTER logradouro,
    ADD COLUMN complemento VARCHAR(100) NULL AFTER numero,
    ADD COLUMN bairro VARCHAR(100) NULL AFTER complemento;
