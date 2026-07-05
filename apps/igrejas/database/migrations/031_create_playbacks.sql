-- Modulo Playbacks: biblioteca de audios (backing tracks) do ministerio de
-- louvor. Liberado em todos os planos (ver Igrejas\Models\Plano) - so o
-- controle de tom (subir/abaixar semitons) e restrito ao plano Plus ou
-- superior.
--
-- Roda no banco de CADA igreja (tabela nao-central, mesma regra da 030).

CREATE TABLE IF NOT EXISTS playbacks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    artista VARCHAR(150) NULL,
    arquivo_path VARCHAR(255) NOT NULL,
    tamanho_bytes INT UNSIGNED NOT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY playbacks_titulo_index (titulo),
    KEY playbacks_status_index (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
