-- Assinaturas de cliente: pacotes pre-pagos de N atendimentos por mes
-- (ex.: "4 cortes por mes por R$120"). A cobranca mensal em si fica
-- FORA do sistema (mesma logica manual do resto do financeiro) - o
-- que o app controla e o consumo: quantos atendimentos o cliente ja
-- usou no ciclo atual (ver Barbearias\Controllers\AssinaturaClienteController).
CREATE TABLE IF NOT EXISTS assinatura_planos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    preco DECIMAL(10, 2) NOT NULL,
    atendimentos_por_mes INT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY assinatura_planos_barbearia_id_index (barbearia_id),
    CONSTRAINT assinatura_planos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "data_inicio" ancora o ciclo mensal (renova a cada mes a partir
-- dessa data, sem precisar de cron pra "resetar" nada - o saldo
-- disponivel e sempre calculado nas consultas, contando os consumos
-- desde o inicio do ciclo atual).
CREATE TABLE IF NOT EXISTS assinaturas_clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    plano_id INT UNSIGNED NOT NULL,
    status ENUM('ativa', 'cancelada') NOT NULL DEFAULT 'ativa',
    data_inicio DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY assinaturas_clientes_barbearia_id_index (barbearia_id),
    KEY assinaturas_clientes_cliente_id_index (cliente_id),
    CONSTRAINT assinaturas_clientes_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT assinaturas_clientes_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE,
    CONSTRAINT assinaturas_clientes_plano_id_foreign
        FOREIGN KEY (plano_id) REFERENCES assinatura_planos (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Um consumo por agendamento (chave unica, mesmo padrao de
-- financeiro_lancamentos.agendamento_id) - marca que aquele
-- atendimento foi "pago" usando o pacote da assinatura, sem gerar
-- lancamento financeiro (a cobranca da mensalidade e feita fora do
-- sistema).
CREATE TABLE IF NOT EXISTS assinatura_consumos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assinatura_id INT UNSIGNED NOT NULL,
    agendamento_id INT UNSIGNED NOT NULL,
    data_consumo DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY assinatura_consumos_agendamento_id_unique (agendamento_id),
    KEY assinatura_consumos_assinatura_id_index (assinatura_id),
    CONSTRAINT assinatura_consumos_assinatura_id_foreign
        FOREIGN KEY (assinatura_id) REFERENCES assinaturas_clientes (id) ON DELETE CASCADE,
    CONSTRAINT assinatura_consumos_agendamento_id_foreign
        FOREIGN KEY (agendamento_id) REFERENCES agendamentos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
