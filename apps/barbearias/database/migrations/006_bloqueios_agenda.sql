-- Bloqueios de agenda: qualquer periodo em que um profissional NAO
-- atende, alem do expediente normal (dias_atendimento/horario_inicio/
-- horario_fim ja existentes em profissionais) - bloqueio manual pontual
-- (ex.: reuniao, compromisso), ferias ou folga. Sempre respeitado pelo
-- calculo de horarios disponiveis do agendamento publico e validado ao
-- criar/editar um agendamento pelo painel.

CREATE TABLE IF NOT EXISTS bloqueios_agenda (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    motivo VARCHAR(150) NULL,
    tipo ENUM('bloqueio', 'ferias', 'folga') NOT NULL DEFAULT 'bloqueio',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY bloqueios_agenda_barbearia_id_index (barbearia_id),
    KEY bloqueios_agenda_profissional_id_index (profissional_id),
    KEY bloqueios_agenda_periodo_index (data_inicio, data_fim),
    CONSTRAINT bloqueios_agenda_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT bloqueios_agenda_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE,
    CONSTRAINT bloqueios_agenda_periodo_check CHECK (data_fim > data_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
