-- KADOSYS Igrejas - Migracao 038
-- Comandos extras do video no painel do operador: volume (0-100), mudo
-- e reiniciar. video_reiniciar_id segue o mesmo padrao de leitura_id
-- (contador que o telao compara via polling, sem precisar incrementar
-- "versao" - reiniciar o MESMO video em exibicao nao muda o conteudo,
-- so pede pro telao voltar ao segundo 0).
--
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central) - mesma regra
-- das outras migracoes de modulos (ex.: 015/016/018/030).

ALTER TABLE projecao_estados
    ADD COLUMN video_volume TINYINT UNSIGNED NOT NULL DEFAULT 100 AFTER video_duracao,
    ADD COLUMN video_mudo TINYINT(1) NOT NULL DEFAULT 0 AFTER video_volume,
    ADD COLUMN video_reiniciar_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER video_mudo;
