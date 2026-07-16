-- Modo de atendimento por fila (ordem de chegada, sem horario marcado) -
-- alternativa ao Agendamento pra barbearia que atende por ordem de
-- chegada. Os dois modos nao convivem: "modo_atendimento" escolhe qual
-- o painel/paginas publicas usam (ver Barbearias\Models\Barbearia e
-- Barbearias\Controllers\FilaController/FilaPublicaController).
ALTER TABLE barbearias
    ADD COLUMN modo_atendimento ENUM('agendamento', 'fila') NOT NULL DEFAULT 'agendamento' AFTER status;

-- Uma linha por pessoa na fila - "entrou_em" define a ordem (FIFO).
-- Cliente pode nao ter cadastro nenhum em "clientes" (fila e pensada
-- pra ser rapida, sem exigir cadastro completo), por isso guarda nome
-- e telefone direto aqui em vez de referenciar cliente_id.
CREATE TABLE IF NOT EXISTS fila_atendimento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NULL,
    nome VARCHAR(150) NOT NULL,
    telefone VARCHAR(20) NULL,
    status ENUM('aguardando', 'em_atendimento', 'atendido', 'cancelado') NOT NULL DEFAULT 'aguardando',
    entrou_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    chamado_em TIMESTAMP NULL,
    atendido_em TIMESTAMP NULL,
    KEY fila_atendimento_barbearia_id_status_index (barbearia_id, status),
    CONSTRAINT fila_atendimento_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT fila_atendimento_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
