-- KADOSYS Igrejas - Migracao 048
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Vincula cada usuario (login) ao seu registro em Membros, pra "Meu
-- perfil" editar endereco/telefone/etc no MESMO lugar que aparece em
-- Membros, em vez de duplicar esses dados em duas tabelas separadas.
-- Nullable: um usuario sem correspondente em Membros ainda funciona
-- normalmente (so fica sem os campos de perfil ate ser vinculado).

ALTER TABLE users
    ADD COLUMN membro_id INT UNSIGNED NULL AFTER foto_path,
    ADD CONSTRAINT users_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE SET NULL;
