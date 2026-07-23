-- KADOSYS Food - Migration 008 (Fase 9: integracao com Superadmin)
-- Rodar uma unica vez em restaurantes ja instalados antes desta
-- migration (instalacoes novas ja recebem isso direto pelo install.sql).

-- Aviso do dono da plataforma (KADOSYS) pros restaurantes cadastrados -
-- so uma linha ativa por vez, visivel pra todos os restaurantes (banco
-- ja compartilhado, sem precisar de coluna restaurante_id aqui).
-- Publicado pelo Super Admin (apps/superadmin), nunca por dentro deste
-- app - mesma semantica de Barbearias\Models\BarbeariaAviso.
CREATE TABLE IF NOT EXISTS restaurante_avisos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mensagem TEXT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY restaurante_avisos_ativo_index (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
