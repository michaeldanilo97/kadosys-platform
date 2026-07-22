-- KADOSYS Igrejas - Migracao 057
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids: Avatar da Crianca. O xp ja existente em
-- kids_criancas (ver migracao 050) define o nivel da crianca (formula
-- calculada em PHP, ver Igrejas\Models\KidsAvatar::nivel()), que por sua
-- vez desbloqueia itens cosmeticos e titulos de um catalogo estatico
-- (tambem em KidsAvatar) - nao ha tabela de catalogo nem de
-- "desbloqueios", so o que a crianca tem EQUIPADO agora, guardado aqui
-- como o slug do item escolhido em cada categoria. A validacao de que o
-- slug equipado realmente esta desbloqueado pro nivel atual acontece no
-- servidor (ver KidsCrianca::atualizarAvatar()), nunca confiando so no
-- que veio do formulario.

ALTER TABLE kids_criancas
    ADD COLUMN avatar_chapeu VARCHAR(40) NULL AFTER pin_bloqueado_ate,
    ADD COLUMN avatar_acessorio VARCHAR(40) NULL AFTER avatar_chapeu,
    ADD COLUMN avatar_fundo VARCHAR(40) NULL AFTER avatar_acessorio,
    ADD COLUMN avatar_titulo VARCHAR(40) NULL AFTER avatar_fundo;
