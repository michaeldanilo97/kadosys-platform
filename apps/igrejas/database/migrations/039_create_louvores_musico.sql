-- KADOSYS Igrejas - Migracao 039
-- Modulo Louvores: letras, cifras e tons dos louvores do ministerio de
-- louvor, com historico de mudancas de tom (cada departamento muda o
-- tom pra tocar e ninguem sabe qual e o "oficial" - esse historico
-- documenta quem mudou, quando e de qual tom pra qual).
--
-- Acesso restrito a usuarios com a flag "musico" (ou admin) - ver
-- User::musico e User::podeAcessarModulo().
--
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central) - mesma regra
-- das outras migracoes de modulos (ex.: 015/016/018/031).

ALTER TABLE users
    ADD COLUMN musico TINYINT(1) NOT NULL DEFAULT 0 AFTER role;

CREATE TABLE IF NOT EXISTS louvores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    letra TEXT NULL,
    tom_atual VARCHAR(20) NULL,
    cifra TEXT NULL,
    anexo_path VARCHAR(255) NULL,
    anexo_nome_original VARCHAR(255) NULL,
    playback_id INT UNSIGNED NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_por INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT louvores_playback_id_foreign
        FOREIGN KEY (playback_id) REFERENCES playbacks (id) ON DELETE SET NULL,
    CONSTRAINT louvores_criado_por_foreign
        FOREIGN KEY (criado_por) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS louvor_tons_historico (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    louvor_id INT UNSIGNED NOT NULL,
    tom_anterior VARCHAR(20) NULL,
    tom_novo VARCHAR(20) NOT NULL,
    observacao VARCHAR(255) NULL,
    alterado_por INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT louvor_tons_historico_louvor_id_foreign
        FOREIGN KEY (louvor_id) REFERENCES louvores (id) ON DELETE CASCADE,
    CONSTRAINT louvor_tons_historico_alterado_por_foreign
        FOREIGN KEY (alterado_por) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
