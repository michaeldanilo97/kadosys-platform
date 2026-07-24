-- Ajuste 173: "missao do dia" (2 conteudos sorteados por crianca, todo
-- dia, com bonus de XP/moedas so nesse dia) + sequencia de dias
-- acessando a Biblioteca em casa - separada da sequencia de presenca
-- fisica na igreja (kids_criancas.sequencia_dias, que so muda no
-- check-in - ver KidsCheckin::registrar()). Objetivo: dar um motivo
-- concreto pra crianca abrir o app hoje, todo dia, sem depender de
-- estar na igreja.

ALTER TABLE kids_criancas
    ADD COLUMN sequencia_app_dias INT UNSIGNED NOT NULL DEFAULT 0 AFTER sequencia_dias,
    ADD COLUMN ultima_visita_app_em DATE NULL AFTER sequencia_app_dias;

CREATE TABLE IF NOT EXISTS kids_missoes_diarias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crianca_id INT UNSIGNED NOT NULL,
    data DATE NOT NULL,
    conteudo_id INT UNSIGNED NOT NULL,
    concluida_em TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_kids_missoes_diarias (crianca_id, data, conteudo_id),
    CONSTRAINT fk_kids_missoes_diarias_crianca
        FOREIGN KEY (crianca_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT fk_kids_missoes_diarias_conteudo
        FOREIGN KEY (conteudo_id) REFERENCES kids_conteudos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
