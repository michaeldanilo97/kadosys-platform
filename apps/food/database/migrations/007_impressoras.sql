-- KADOSYS Food - Migration 007 (Fase 9: Configuracoes)
-- Rodar uma unica vez em restaurantes ja instalados antes desta
-- migration (instalacoes novas ja recebem isso direto pelo install.sql).

-- Impressoras cadastradas so pra referencia (nome/IP) - NAO existe
-- integracao real com driver/protocolo ESC-POS nesta entrega, o
-- comprovante do PDV continua saindo pela impressao do navegador (ver
-- CaixaController). Serve pra equipe lembrar qual impressora de rede
-- fica em qual setor (cozinha/balcao).
CREATE TABLE IF NOT EXISTS impressoras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    ip VARCHAR(45) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY impressoras_restaurante_id_index (restaurante_id),
    CONSTRAINT impressoras_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
