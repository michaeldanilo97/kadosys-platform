-- KADOSYS Igrejas - Migracao 008
-- Guarda o tempo atual/duracao do video do YouTube em projecao, para o
-- painel do operador mostrar o progresso exato (ele nao tem acesso
-- direto ao player, que so existe no telao).
--
-- Atualizado por um endpoint proprio que NAO incrementa "versao" (o
-- tempo muda a cada poucos segundos e nao deve disparar o re-render
-- completo do estado nas outras telas).

ALTER TABLE projecao_estados
    ADD COLUMN video_tempo_atual SMALLINT UNSIGNED NULL AFTER video_estado,
    ADD COLUMN video_duracao SMALLINT UNSIGNED NULL AFTER video_tempo_atual;
