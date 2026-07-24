-- Ajuste 174: "e possivel permitir que as criancas joguem jogos juntos
-- online?" - duelo de quiz 1x1 entre duas criancas da MESMA igreja
-- (nunca entre igrejas diferentes - cada banco e de uma igreja so, o
-- isolamento e automatico), sem chat de texto livre (so reacoes de
-- emoji pre-definidas), atualizado por polling (sem precisar de
-- servidor de WebSocket, que nao existe nessa hospedagem).

CREATE TABLE IF NOT EXISTS kids_duelos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conteudo_id INT UNSIGNED NOT NULL,
    criador_id INT UNSIGNED NOT NULL,
    convidado_id INT UNSIGNED NOT NULL,
    status ENUM('pendente', 'aceito', 'recusado', 'em_andamento', 'finalizado') NOT NULL DEFAULT 'pendente',
    criador_progresso INT UNSIGNED NOT NULL DEFAULT 0,
    convidado_progresso INT UNSIGNED NOT NULL DEFAULT 0,
    criador_terminado_em TIMESTAMP NULL,
    convidado_terminado_em TIMESTAMP NULL,
    vencedor_id INT UNSIGNED NULL,
    reacao_criador VARCHAR(8) NULL,
    reacao_criador_em TIMESTAMP NULL,
    reacao_convidado VARCHAR(8) NULL,
    reacao_convidado_em TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY kids_duelos_convidado_status_index (convidado_id, status),
    KEY kids_duelos_criador_status_index (criador_id, status),
    CONSTRAINT fk_kids_duelos_conteudo
        FOREIGN KEY (conteudo_id) REFERENCES kids_conteudos (id) ON DELETE CASCADE,
    CONSTRAINT fk_kids_duelos_criador
        FOREIGN KEY (criador_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT fk_kids_duelos_convidado
        FOREIGN KEY (convidado_id) REFERENCES kids_criancas (id) ON DELETE CASCADE,
    CONSTRAINT fk_kids_duelos_vencedor
        FOREIGN KEY (vencedor_id) REFERENCES kids_criancas (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
