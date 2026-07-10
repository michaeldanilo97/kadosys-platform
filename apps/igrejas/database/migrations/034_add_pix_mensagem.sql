-- KADOSYS Igrejas - Migracao 034
-- Mensagem opcional exibida na tela de Pix do telao (junto com os QR de
-- dizimo/oferta e a logo da igreja) - a igreja escolhe entre um texto
-- livre (aviso, versiculo digitado a mao etc.) ou uma referencia biblica
-- que e resolvida na hora, buscando o texto atual na tabela
-- biblia_versiculos (ver ProjecaoEstado::montarPixJson()).

ALTER TABLE configuracoes_igreja
    ADD COLUMN pix_mensagem_tipo ENUM('nenhuma', 'texto', 'versiculo') NOT NULL DEFAULT 'nenhuma' AFTER pix_nome_beneficiario,
    ADD COLUMN pix_mensagem_texto TEXT NULL AFTER pix_mensagem_tipo,
    ADD COLUMN pix_mensagem_biblia_versao VARCHAR(10) NULL AFTER pix_mensagem_texto,
    ADD COLUMN pix_mensagem_livro_id TINYINT UNSIGNED NULL AFTER pix_mensagem_biblia_versao,
    ADD COLUMN pix_mensagem_capitulo SMALLINT UNSIGNED NULL AFTER pix_mensagem_livro_id,
    ADD COLUMN pix_mensagem_versiculo_inicio SMALLINT UNSIGNED NULL AFTER pix_mensagem_capitulo,
    ADD COLUMN pix_mensagem_versiculo_fim SMALLINT UNSIGNED NULL AFTER pix_mensagem_versiculo_inicio,
    ADD CONSTRAINT configuracoes_igreja_pix_mensagem_livro_id_foreign
        FOREIGN KEY (pix_mensagem_livro_id) REFERENCES biblia_livros (id) ON DELETE SET NULL;
