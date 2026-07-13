-- KADOSYS Igrejas - Migracao 040
-- Programacao de culto (repertorio): o lider de louvor monta a ordem
-- dos louvores de um culto e acompanha/avanca a musica atual em tempo
-- real (por polling, mesmo mecanismo ja usado em Projecao/Telao - sem
-- WebSocket, funciona na hospedagem compartilhada). Os demais musicos
-- so acompanham (Modo Culto) e trocam avisos rapidos entre si.
--
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central) - mesma regra
-- das outras migracoes de modulo (ex.: 039).

ALTER TABLE users
    ADD COLUMN lider_louvor TINYINT(1) NOT NULL DEFAULT 0 AFTER musico;

ALTER TABLE louvores
    ADD COLUMN andamento_bpm SMALLINT UNSIGNED NULL AFTER tom_atual;

CREATE TABLE IF NOT EXISTS repertorios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    status ENUM('planejado', 'encerrado') NOT NULL DEFAULT 'planejado',
    atual_item_id INT UNSIGNED NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    criado_por INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT repertorios_criado_por_foreign
        FOREIGN KEY (criado_por) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repertorio_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repertorio_id INT UNSIGNED NOT NULL,
    louvor_id INT UNSIGNED NOT NULL,
    ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT repertorio_itens_repertorio_id_foreign
        FOREIGN KEY (repertorio_id) REFERENCES repertorios (id) ON DELETE CASCADE,
    CONSTRAINT repertorio_itens_louvor_id_foreign
        FOREIGN KEY (louvor_id) REFERENCES louvores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- So depois de repertorio_itens existir, pra poder referenciar.
ALTER TABLE repertorios
    ADD CONSTRAINT repertorios_atual_item_id_foreign
        FOREIGN KEY (atual_item_id) REFERENCES repertorio_itens (id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS repertorio_mensagens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repertorio_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    texto VARCHAR(280) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT repertorio_mensagens_repertorio_id_foreign
        FOREIGN KEY (repertorio_id) REFERENCES repertorios (id) ON DELETE CASCADE,
    CONSTRAINT repertorio_mensagens_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
