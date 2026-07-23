-- KADOSYS Academias - Migration 004
-- Rodar uma unica vez em academias ja instaladas antes desta migration
-- (instalacoes novas ja recebem isso direto pelo install.sql).

-- Avaliacao fisica periodica (bioimpedancia simplificada) registrada
-- pelo professor - peso + medidas opcionais. E a fonte do grafico de
-- evolucao (peso/%gordura) no painel do aluno.
CREATE TABLE IF NOT EXISTS avaliacoes_fisicas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    aluno_id INT UNSIGNED NOT NULL,
    professor_id INT UNSIGNED NULL,
    data_avaliacao DATE NOT NULL,
    peso_kg DECIMAL(6,2) NOT NULL,
    percentual_gordura DECIMAL(5,2) NULL,
    medida_peito_cm DECIMAL(5,2) NULL,
    medida_cintura_cm DECIMAL(5,2) NULL,
    medida_quadril_cm DECIMAL(5,2) NULL,
    medida_braco_cm DECIMAL(5,2) NULL,
    medida_coxa_cm DECIMAL(5,2) NULL,
    observacao TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY avaliacoes_fisicas_academia_id_index (academia_id),
    KEY avaliacoes_fisicas_aluno_id_index (aluno_id),
    CONSTRAINT avaliacoes_fisicas_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE,
    CONSTRAINT avaliacoes_fisicas_aluno_id_foreign
        FOREIGN KEY (aluno_id) REFERENCES alunos (id) ON DELETE CASCADE,
    CONSTRAINT avaliacoes_fisicas_professor_id_foreign
        FOREIGN KEY (professor_id) REFERENCES professores (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
