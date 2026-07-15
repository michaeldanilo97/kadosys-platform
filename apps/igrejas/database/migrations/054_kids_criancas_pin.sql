-- KADOSYS Igrejas - Migracao 054
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids: login proprio da crianca via PIN de 4 digitos,
-- so pode ser gerado se a crianca ja tiver um responsavel vinculado
-- (responsavel_membro_id, ver kids_criancas) - isso funciona como o
-- consentimento minimo exigido antes de liberar acesso independente
-- pra crianca (ver KidsCrianca::gerarESalvarPin()). O PIN e guardado
-- com hash (nunca em texto puro), e as colunas de tentativas/bloqueio
-- protegem contra tentativa por forca bruta, ja que 4 digitos e um
-- espaco de busca pequeno.

ALTER TABLE kids_criancas
    ADD COLUMN pin_hash VARCHAR(255) NULL AFTER sequencia_dias,
    ADD COLUMN pin_definido_em TIMESTAMP NULL AFTER pin_hash,
    ADD COLUMN pin_tentativas_invalidas TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER pin_definido_em,
    ADD COLUMN pin_bloqueado_ate TIMESTAMP NULL AFTER pin_tentativas_invalidas;
