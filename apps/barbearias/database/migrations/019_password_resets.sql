-- Recuperacao de senha ("esqueci minha senha") - mesmo esquema usado no
-- KADOSYS Igrejas, so que aqui de fato termina o fluxo (o Igrejas so
-- gera o token, nunca chegou a enviar o e-mail nem ter a tela de
-- redefinir).
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY password_resets_email_index (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lembrete automatico por e-mail: marca quando o cliente ja foi
-- avisado sobre o agendamento (ver cron/enviar_lembretes_agendamento.php),
-- pra nunca mandar duas vezes mesmo se o cron rodar de novo no mesmo dia.
ALTER TABLE agendamentos ADD COLUMN lembrete_enviado_em DATETIME NULL AFTER status;
