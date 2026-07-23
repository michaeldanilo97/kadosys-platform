-- KADOSYS Food - Migration 006 (Fase 7: Financeiro completo)
-- Rodar uma unica vez em restaurantes ja instalados antes desta
-- migration (instalacoes novas ja recebem isso direto pelo install.sql).

-- Centro de custo - agrupamento opcional de contas a pagar/receber
-- (ex.: "Cozinha", "Delivery", "Administrativo"). So um nome, sem
-- hierarquia - se precisar de sub-centros no futuro, adiciona depois.
CREATE TABLE IF NOT EXISTS centros_custo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY centros_custo_restaurante_id_index (restaurante_id),
    CONSTRAINT centros_custo_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conta a pagar (despesa fixa/variavel/parcelada/recorrente). "status"
-- so guarda 'pendente'/'paga'/'cancelada' de proposito - "vencida" NAO
-- e um status persistido (evitaria precisar de um cron so pra ficar
-- passando linhas de pendente pra vencida todo dia, com risco de ficar
-- desatualizado se o cron nao rodar); a tela calcula "vencida" na hora
-- (pendente + vencimento < hoje) e filtra por WHERE tambem na hora.
-- "serie_id" agrupa as parcelas/repeticoes de uma MESMA despesa
-- recorrente - a primeira linha da serie aponta pra si mesma
-- (serie_id = id), as geradas depois (ver
-- ContaPagar::gerarProximasRecorrentes(), rodado pelo cron mensal)
-- copiam o mesmo serie_id. "parcela_total" NULL = recorrencia sem fim
-- definido (ex.: aluguel); preenchido = expira depois de N parcelas
-- (ex.: 12x).
CREATE TABLE IF NOT EXISTS contas_a_pagar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    centro_custo_id INT UNSIGNED NULL,
    serie_id INT UNSIGNED NULL,
    descricao VARCHAR(255) NOT NULL,
    categoria VARCHAR(60) NULL,
    valor DECIMAL(10, 2) NOT NULL,
    vencimento DATE NOT NULL,
    status ENUM('pendente', 'paga', 'cancelada') NOT NULL DEFAULT 'pendente',
    pago_em DATE NULL,
    anexo_path VARCHAR(255) NULL,
    recorrente TINYINT(1) NOT NULL DEFAULT 0,
    parcela_atual SMALLINT UNSIGNED NULL,
    parcela_total SMALLINT UNSIGNED NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY contas_a_pagar_restaurante_id_index (restaurante_id),
    KEY contas_a_pagar_serie_id_index (serie_id),
    KEY contas_a_pagar_vencimento_index (vencimento),
    CONSTRAINT contas_a_pagar_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE,
    CONSTRAINT contas_a_pagar_centro_custo_id_foreign
        FOREIGN KEY (centro_custo_id) REFERENCES centros_custo (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conta a receber (o espelho de contas_a_pagar) - cobre recebiveis que
-- nao passam pelo fluxo automatico de Pedido::finalizar() (ex.: um
-- combinado de pagamento futuro com um cliente/fornecedor de eventos).
CREATE TABLE IF NOT EXISTS contas_a_receber (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    centro_custo_id INT UNSIGNED NULL,
    cliente_id INT UNSIGNED NULL,
    descricao VARCHAR(255) NOT NULL,
    categoria VARCHAR(60) NULL,
    valor DECIMAL(10, 2) NOT NULL,
    vencimento DATE NOT NULL,
    status ENUM('pendente', 'recebida', 'cancelada') NOT NULL DEFAULT 'pendente',
    recebido_em DATE NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY contas_a_receber_restaurante_id_index (restaurante_id),
    KEY contas_a_receber_vencimento_index (vencimento),
    CONSTRAINT contas_a_receber_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE,
    CONSTRAINT contas_a_receber_centro_custo_id_foreign
        FOREIGN KEY (centro_custo_id) REFERENCES centros_custo (id) ON DELETE SET NULL,
    CONSTRAINT contas_a_receber_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
