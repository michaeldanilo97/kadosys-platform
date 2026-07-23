-- KADOSYS Academias - Migration 002
-- Rodar uma unica vez em academias ja instaladas antes desta migration
-- (instalacoes novas ja recebem isso direto pelo install.sql).
--
-- As colunas de gamificacao (streak_atual, streak_recorde,
-- pontos_frequencia, ultimo_checkin_dia) e qr_checkin_token ja existem
-- desde a Fase 1 (install.sql original) - so faltava esta tabela de
-- registro de check-in/checkout.

-- Registro de check-in/checkout via QR fixo. "saida_em" NULL = aluno
-- ainda esta dentro da academia (check-in em aberto). Cada academia so
-- pode ter, no maximo, um check-in em aberto por aluno de cada vez
-- (garantido em codigo, ver Academias\Models\AcademiaCheckin::emAberto).
CREATE TABLE IF NOT EXISTS academia_checkins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    aluno_id INT UNSIGNED NOT NULL,
    entrada_em DATETIME NOT NULL,
    saida_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY academia_checkins_academia_id_index (academia_id),
    KEY academia_checkins_aluno_id_index (aluno_id),
    KEY academia_checkins_aluno_saida_index (aluno_id, saida_em),
    CONSTRAINT academia_checkins_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE,
    CONSTRAINT academia_checkins_aluno_id_foreign
        FOREIGN KEY (aluno_id) REFERENCES alunos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
