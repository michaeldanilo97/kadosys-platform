-- Ajuste 172: avatar da crianca vira um "boneco" de verdade (corpo +
-- tom de pele + roupa em camadas SVG) em vez de emojis soltos em cima
-- de um circulo com a inicial do nome - ver KidsAvatar::catalogoPeles()
-- e catalogoRoupas() (novos catalogos estaticos, no mesmo padrao dos
-- ja existentes de chapeu/acessorio/fundo/titulo).

ALTER TABLE kids_criancas
    ADD COLUMN avatar_pele VARCHAR(40) NULL AFTER avatar_titulo,
    ADD COLUMN avatar_roupa VARCHAR(40) NULL AFTER avatar_pele;
