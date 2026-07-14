-- KADOSYS Igrejas - Migracao 049
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids (Fase 1 - operacional): turmas do ministerio
-- infantil, agrupadas por faixa etaria, com um professor responsavel
-- opcional (vinculado a Membros, mesmo padrao usado em lider de Grupos
-- e Ministerios).

CREATE TABLE IF NOT EXISTS kids_turmas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    faixa_etaria_min TINYINT UNSIGNED NULL,
    faixa_etaria_max TINYINT UNSIGNED NULL,
    professor_membro_id INT UNSIGNED NULL,
    descricao TEXT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY kids_turmas_status_index (status),
    CONSTRAINT kids_turmas_professor_membro_id_foreign
        FOREIGN KEY (professor_membro_id) REFERENCES membros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
