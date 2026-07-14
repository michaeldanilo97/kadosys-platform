-- KADOSYS Igrejas - Migracao 044
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- 1) Agenda: eventos privados - o proprio usuario pode cadastrar um
--    compromisso que so ele ve (ex.: visita pessoal), sem aparecer pra
--    mais ninguem. Eventos publicos (cadastrados por admin/lider, ex.:
--    ensaio do grupo) continuam visiveis pra todo mundo, como ja era.
-- 2) Aniversariantes: mensagem de parabens personalizada (Configuracoes)
--    e tabela de controle pra nao enviar o e-mail duas vezes no mesmo
--    ano pro mesmo membro.

ALTER TABLE agenda_eventos
    ADD COLUMN visibilidade ENUM('publico', 'privado') NOT NULL DEFAULT 'publico' AFTER status,
    ADD COLUMN criado_por_user_id INT UNSIGNED NULL AFTER visibilidade,
    ADD CONSTRAINT agenda_eventos_criado_por_user_id_foreign
        FOREIGN KEY (criado_por_user_id) REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE configuracoes_igreja
    ADD COLUMN mensagem_aniversario TEXT NULL;

CREATE TABLE IF NOT EXISTS aniversario_envios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    membro_id INT UNSIGNED NOT NULL,
    ano SMALLINT UNSIGNED NOT NULL,
    enviado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY aniversario_envios_unique (membro_id, ano),
    CONSTRAINT aniversario_envios_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
