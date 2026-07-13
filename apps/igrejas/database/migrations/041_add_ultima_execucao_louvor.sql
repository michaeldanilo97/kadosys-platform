-- KADOSYS Igrejas - Migracao 041
-- Registra a ultima vez que cada louvor tocou de verdade num culto
-- (quando vira a musica "atual" no Modo Culto, ver
-- Repertorio::definirAtual()/Louvor::marcarExecutado()) - ajuda o time
-- a variar o repertorio em vez de repetir sempre os mesmos louvores.
--
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central) - mesma regra
-- das outras migracoes de modulo (ex.: 039/040).

ALTER TABLE louvores
    ADD COLUMN ultima_execucao TIMESTAMP NULL DEFAULT NULL AFTER status;
