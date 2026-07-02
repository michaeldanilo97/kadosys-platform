-- KADOSYS Igrejas - Migracao 007
-- Adiciona a marcacao a lapis do preletor ao estado de projecao, para que
-- o que ele desenha sobre o texto seja sincronizado (via polling) e
-- exibido tambem no telao - e nao apenas localmente no tablet dele.
--
-- Guardamos os tracos como JSON (lista de tracos, cada um uma lista de
-- pontos {x,y} em fracao 0..1 do "palco" de projecao) porque e um dado
-- efemero, pequeno e sem necessidade de consulta/indexacao - nao
-- justifica uma tabela propria.

ALTER TABLE projecao_estados
    ADD COLUMN biblia_marcacao TEXT NULL AFTER biblia_versao;
