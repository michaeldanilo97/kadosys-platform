-- KADOSYS Igrejas - Migracao 011
-- Registro central de tenants (igrejas) provisionadas automaticamente via
-- cadastro publico + assinatura. Vive no MESMO banco desta instalacao
-- (kadosys1_igrejas) por pragmatismo - nao criamos um banco "de
-- plataforma" separado so pra isso. Fica claramente namespaced com o
-- prefixo "plataforma_" pra nao se confundir com os dados desta igreja
-- (membros, cultos, etc.), que continuam intocados.
--
-- "plataforma_provisionamentos" guarda cada tentativa de cadastro (uma
-- linha por vez que alguem preenche o formulario e vai pro checkout),
-- desde antes do pagamento ser confirmado ate o banco da nova igreja
-- estar pronto (ou ter dado erro, com a mensagem registrada).
--
-- "plataforma_tenants" so ganha uma linha quando o provisionamento
-- realmente terminou com sucesso - e o "diretorio" de igrejas ativas,
-- usado depois pra resolver qual banco usar a partir do subdominio.
--
-- IMPORTANTE: esta migracao roda SO nesta instalacao central (a que
-- recebe os cadastros publicos) - de proposito NAO faz parte do
-- database/install.sql, porque install.sql tambem e usado pra criar o
-- banco de cada igreja nova provisionada automaticamente, e essas
-- tabelas de plataforma nao tem nada a ver com o banco individual de
-- cada igreja.

CREATE TABLE IF NOT EXISTS plataforma_provisionamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_igreja VARCHAR(150) NOT NULL,
    slug VARCHAR(40) NOT NULL,
    admin_nome VARCHAR(150) NOT NULL,
    admin_email VARCHAR(190) NOT NULL,
    admin_senha_hash VARCHAR(255) NOT NULL,
    plano ENUM('essencial', 'premium') NOT NULL,
    mp_preapproval_id VARCHAR(64) NULL,
    status ENUM(
        'aguardando_pagamento',
        'pago_aguardando_provisionamento',
        'provisionando',
        'concluido',
        'erro'
    ) NOT NULL DEFAULT 'aguardando_pagamento',
    erro_mensagem TEXT NULL,
    tenant_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_provisionamento_mp_preapproval_id (mp_preapproval_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plataforma_tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(40) NOT NULL,
    nome_igreja VARCHAR(150) NOT NULL,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL,
    subdominio VARCHAR(190) NOT NULL,
    db_name VARCHAR(64) NOT NULL,
    db_user VARCHAR(64) NOT NULL,
    db_password VARCHAR(255) NOT NULL,
    status ENUM('provisionando', 'ativo', 'erro', 'suspenso') NOT NULL DEFAULT 'provisionando',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tenant_slug (slug),
    UNIQUE KEY uq_tenant_subdominio (subdominio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE plataforma_provisionamentos
    ADD CONSTRAINT fk_provisionamento_tenant
        FOREIGN KEY (tenant_id) REFERENCES plataforma_tenants(id) ON DELETE SET NULL;
