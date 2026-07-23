-- KADOSYS Academias - Migration 003
-- Rodar uma unica vez em academias ja instaladas antes desta migration
-- (instalacoes novas ja recebem isso direto pelo install.sql).

-- Ficha de treino prescrita pelo professor pra um aluno. Uma academia
-- pode ter varias fichas ativas ao mesmo tempo pro mesmo aluno (ex.:
-- "Treino A" e "Treino B" alternados) - por isso "ativa" nao e
-- exclusiva, e o painel do aluno mostra todas as fichas ativas dele.
CREATE TABLE IF NOT EXISTS fichas_treino (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    aluno_id INT UNSIGNED NOT NULL,
    professor_id INT UNSIGNED NULL,
    nome VARCHAR(191) NOT NULL,
    objetivo VARCHAR(191) NULL,
    validade_ate DATE NULL,
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY fichas_treino_academia_id_index (academia_id),
    KEY fichas_treino_aluno_id_index (aluno_id),
    CONSTRAINT fichas_treino_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE,
    CONSTRAINT fichas_treino_aluno_id_foreign
        FOREIGN KEY (aluno_id) REFERENCES alunos (id) ON DELETE CASCADE,
    CONSTRAINT fichas_treino_professor_id_foreign
        FOREIGN KEY (professor_id) REFERENCES professores (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exercicio dentro de uma ficha, na ordem que deve ser executado.
CREATE TABLE IF NOT EXISTS ficha_exercicios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ficha_id INT UNSIGNED NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    nome_exercicio VARCHAR(191) NOT NULL,
    grupo_muscular VARCHAR(100) NULL,
    series INT UNSIGNED NULL,
    repeticoes VARCHAR(50) NULL,
    carga_sugerida_kg DECIMAL(6,2) NULL,
    descanso_segundos INT UNSIGNED NULL,
    observacao TEXT NULL,
    KEY ficha_exercicios_ficha_id_index (ficha_id),
    CONSTRAINT ficha_exercicios_ficha_id_foreign
        FOREIGN KEY (ficha_id) REFERENCES fichas_treino (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cada vez que o aluno marca um exercicio como feito no "treino de
-- hoje" (com a carga que usou) vira uma linha aqui - e a fonte do
-- grafico de evolucao de carga. Unique por (exercicio, aluno, dia):
-- marcar de novo no mesmo dia atualiza a mesma linha, nao duplica.
CREATE TABLE IF NOT EXISTS treino_execucoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ficha_exercicio_id INT UNSIGNED NOT NULL,
    aluno_id INT UNSIGNED NOT NULL,
    data_execucao DATE NOT NULL,
    carga_usada_kg DECIMAL(6,2) NULL,
    series_completas INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY treino_execucoes_unico (ficha_exercicio_id, aluno_id, data_execucao),
    KEY treino_execucoes_aluno_id_index (aluno_id),
    CONSTRAINT treino_execucoes_ficha_exercicio_id_foreign
        FOREIGN KEY (ficha_exercicio_id) REFERENCES ficha_exercicios (id) ON DELETE CASCADE,
    CONSTRAINT treino_execucoes_aluno_id_foreign
        FOREIGN KEY (aluno_id) REFERENCES alunos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
