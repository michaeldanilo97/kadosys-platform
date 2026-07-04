-- KADOSYS Igrejas - Migracao 030
-- Leitura em voz alta do texto biblico projetado ("Ler agora" no painel
-- do operador). leitura_id e um contador: cada clique em "Ler agora"
-- incrementa, e o telao (que roda o sintetizador de voz do navegador,
-- ver telao.js) detecta o aumento via polling e le o texto atual.
--
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central) - mesma regra
-- das outras migracoes de modulos (ex.: 015/016/018).

ALTER TABLE projecao_estados
    ADD COLUMN leitura_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER versao;
