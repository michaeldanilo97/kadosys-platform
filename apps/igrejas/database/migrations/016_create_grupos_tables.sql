-- KADOSYS Igrejas - Migracao 016
-- Tabelas do modulo Grupos: pequenos grupos, celulas e classes - mesma
-- estrutura do modulo Ministerios (lider opcional + participantes em
-- relacao muitos-para-muitos via tabela pivot), com campos a mais pra
-- registrar dia da semana/horario/local do encontro.

CREATE TABLE IF NOT EXISTS grupos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo ENUM('celula', 'classe', 'grupo') NOT NULL DEFAULT 'grupo',
    descricao TEXT NULL,
    lider_membro_id INT UNSIGNED NULL,
    dia_semana ENUM('domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado') NULL,
    horario TIME NULL,
    local VARCHAR(150) NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY grupos_nome_index (nome),
    KEY grupos_status_index (status),
    CONSTRAINT grupos_lider_membro_id_foreign
        FOREIGN KEY (lider_membro_id) REFERENCES membros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS grupo_membros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT UNSIGNED NOT NULL,
    membro_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY grupo_membros_unique (grupo_id, membro_id),
    CONSTRAINT grupo_membros_grupo_id_foreign
        FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE CASCADE,
    CONSTRAINT grupo_membros_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
