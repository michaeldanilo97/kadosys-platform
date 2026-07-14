-- KADOSYS Igrejas - Migracao 042
-- Perfil de equipe (tela "Equipe", estilo rede social): cada usuario
-- pode colocar uma foto, um cargo (musico/midia/equipamento/membro) e,
-- quando musico, um instrumento - usado pra montar cards com nome,
-- foto e um icone/badge do cargo, junto de "Ao vivo" (Projecao e
-- Louvores) ja destacados no menu.
--
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central) - mesma regra
-- das outras migracoes de modulo (ex.: 039/040/041).

ALTER TABLE users
    ADD COLUMN cargo ENUM('musico', 'midia', 'equipamento', 'membro') NOT NULL DEFAULT 'membro' AFTER lider_louvor,
    ADD COLUMN instrumento VARCHAR(20) NULL AFTER cargo,
    ADD COLUMN foto_path VARCHAR(255) NULL AFTER instrumento;
