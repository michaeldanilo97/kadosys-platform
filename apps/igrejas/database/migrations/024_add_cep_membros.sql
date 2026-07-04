-- KADOSYS Igrejas - Migracao 024
-- CEP do membro - usado no auto-cadastro publico de membros (ver
-- MembroPublicoController) pra autopreencher endereco/cidade/estado via
-- a API do ViaCEP, mas tambem fica disponivel no cadastro manual pelo
-- painel.

ALTER TABLE membros
    ADD COLUMN cep VARCHAR(9) NULL AFTER endereco;
