-- KADOSYS Igrejas - Migracao 050
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Modulo KADOSYS Kids (Fase 1 - operacional): perfil de cada crianca
-- cadastrada no ministerio infantil - dados basicos, turma, responsavel
-- (vinculado a um Membro quando o pai/mae ja e cadastrado, com um
-- nome/telefone avulso como alternativa), informacoes de seguranca
-- (autorizados a retirar, alergias/observacoes medicas) e colunas
-- minimas de gamificacao (xp/moedas/sequencia), incrementadas a cada
-- check-in (ver kids_checkins e KidsCrianca::registrarCheckin()).

CREATE TABLE IF NOT EXISTS kids_criancas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    foto_path VARCHAR(255) NULL,
    data_nascimento DATE NULL,
    genero ENUM('masculino', 'feminino') NULL,
    turma_id INT UNSIGNED NULL,
    responsavel_membro_id INT UNSIGNED NULL,
    responsavel_nome VARCHAR(150) NULL,
    responsavel_telefone VARCHAR(20) NULL,
    autorizados_retirada TEXT NULL,
    alergias TEXT NULL,
    observacoes_medicas TEXT NULL,
    observacoes TEXT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    xp INT UNSIGNED NOT NULL DEFAULT 0,
    moedas INT UNSIGNED NOT NULL DEFAULT 0,
    sequencia_dias INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY kids_criancas_status_index (status),
    KEY kids_criancas_turma_id_index (turma_id),
    CONSTRAINT kids_criancas_turma_id_foreign
        FOREIGN KEY (turma_id) REFERENCES kids_turmas (id) ON DELETE SET NULL,
    CONSTRAINT kids_criancas_responsavel_membro_id_foreign
        FOREIGN KEY (responsavel_membro_id) REFERENCES membros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
