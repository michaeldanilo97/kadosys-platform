-- Multi-unidade (filiais). Toda barbearia existente ganha uma
-- "Unidade Principal" automatica e todo profissional existente e
-- vinculado a ela no backfill abaixo - ninguem perde funcionalidade
-- nem precisa configurar nada na hora: quem continuar com uma unidade
-- so nao ve NENHUMA tela nova (o painel so pede pra escolher a unidade
-- quando ha mais de uma ativa).

CREATE TABLE IF NOT EXISTS unidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    endereco VARCHAR(255) NULL,
    cidade VARCHAR(100) NULL,
    estado CHAR(2) NULL,
    cep VARCHAR(9) NULL,
    telefone VARCHAR(20) NULL,
    whatsapp VARCHAR(20) NULL,
    principal TINYINT(1) NOT NULL DEFAULT 0,
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unidades_barbearia_id_slug_unique (barbearia_id, slug),
    KEY unidades_barbearia_id_index (barbearia_id),
    CONSTRAINT unidades_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS profissional_unidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profissional_id INT UNSIGNED NOT NULL,
    unidade_id INT UNSIGNED NOT NULL,
    UNIQUE KEY profissional_unidades_unique (profissional_id, unidade_id),
    KEY profissional_unidades_unidade_id_index (unidade_id),
    CONSTRAINT profissional_unidades_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE,
    CONSTRAINT profissional_unidades_unidade_id_foreign
        FOREIGN KEY (unidade_id) REFERENCES unidades (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE agendamentos ADD COLUMN unidade_id INT UNSIGNED NULL AFTER barbearia_id;
ALTER TABLE agendamentos ADD KEY agendamentos_unidade_id_index (unidade_id);
ALTER TABLE agendamentos ADD CONSTRAINT agendamentos_unidade_id_foreign
    FOREIGN KEY (unidade_id) REFERENCES unidades (id) ON DELETE SET NULL;

-- Backfill: unidade principal automatica pra toda barbearia que ainda
-- nao tem nenhuma unidade cadastrada.
INSERT INTO unidades (barbearia_id, nome, slug, principal, ativa, created_at)
SELECT b.id, 'Unidade Principal', 'principal', 1, 1, NOW()
FROM barbearias b
WHERE NOT EXISTS (SELECT 1 FROM unidades u WHERE u.barbearia_id = b.id);

-- Backfill: todo profissional existente passa a atender na unidade
-- principal da propria barbearia.
INSERT INTO profissional_unidades (profissional_id, unidade_id)
SELECT p.id, u.id
FROM profissionais p
INNER JOIN unidades u ON u.barbearia_id = p.barbearia_id AND u.principal = 1
WHERE NOT EXISTS (
    SELECT 1 FROM profissional_unidades pu WHERE pu.profissional_id = p.id
);
