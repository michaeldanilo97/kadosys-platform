-- Lista de espera: cliente entra na fila quando nao ha nenhum horario
-- livre no dia escolhido (agendamento publico) - a equipe ve a fila no
-- painel e entra em contato manualmente quando um horario abrir (nao
-- ha notificacao automatica, ver DEPLOY_LOG).

CREATE TABLE IF NOT EXISTS lista_espera (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NULL,
    servico_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    data_desejada DATE NOT NULL,
    observacoes VARCHAR(255) NULL,
    status ENUM('aguardando', 'atendido', 'cancelado') NOT NULL DEFAULT 'aguardando',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY lista_espera_barbearia_id_index (barbearia_id),
    KEY lista_espera_status_index (status),
    CONSTRAINT lista_espera_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT lista_espera_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE SET NULL,
    CONSTRAINT lista_espera_servico_id_foreign
        FOREIGN KEY (servico_id) REFERENCES servicos (id) ON DELETE CASCADE,
    CONSTRAINT lista_espera_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
