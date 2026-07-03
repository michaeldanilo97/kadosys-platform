-- KADOSYS Igrejas - Migracao 017
-- Tabela do modulo Agenda: eventos, reunioes e reservas de espaco da
-- igreja, com responsavel opcional (um membro).

CREATE TABLE IF NOT EXISTS agenda_eventos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    tipo ENUM('evento', 'reuniao', 'reserva', 'outro') NOT NULL DEFAULT 'evento',
    data DATE NOT NULL,
    hora_inicio TIME NULL,
    hora_fim TIME NULL,
    local VARCHAR(150) NULL,
    responsavel_membro_id INT UNSIGNED NULL,
    descricao TEXT NULL,
    status ENUM('agendado', 'realizado', 'cancelado') NOT NULL DEFAULT 'agendado',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY agenda_eventos_data_index (data),
    KEY agenda_eventos_status_index (status),
    CONSTRAINT agenda_eventos_responsavel_membro_id_foreign
        FOREIGN KEY (responsavel_membro_id) REFERENCES membros (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
