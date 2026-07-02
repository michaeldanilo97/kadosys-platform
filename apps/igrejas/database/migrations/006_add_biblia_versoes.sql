-- KADOSYS Igrejas - Migracao 006
-- Adiciona suporte a multiplas versoes/traducoes da Biblia (o operador e
-- o preletor passam a poder escolher qual versao consultar). As versoes
-- disponiveis (nvi, acf, aa) sao fixas no codigo (Igrejas\Models\BibliaVersao),
-- nao precisam de tabela propria.
--
-- Esta e uma migracao INCREMENTAL: roda depois da 005, que ja foi
-- aplicada em producao. Nao altera a 005 (ja executada), apenas evolui
-- o schema existente.

ALTER TABLE biblia_versiculos
    ADD COLUMN versao VARCHAR(10) NOT NULL DEFAULT 'nvi' AFTER livro_id;

ALTER TABLE biblia_versiculos
    DROP INDEX biblia_versiculos_unique,
    DROP INDEX biblia_versiculos_capitulo_index;

ALTER TABLE biblia_versiculos
    ADD UNIQUE KEY biblia_versiculos_unique (versao, livro_id, capitulo, versiculo),
    ADD KEY biblia_versiculos_capitulo_index (versao, livro_id, capitulo);

ALTER TABLE projecao_estados
    ADD COLUMN biblia_versao VARCHAR(10) NULL DEFAULT 'nvi' AFTER livro_id;
