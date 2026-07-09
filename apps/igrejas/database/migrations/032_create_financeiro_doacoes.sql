-- KADOSYS Igrejas - Migracao 032
-- Doacao via Pix estatico (chave da propria igreja, sem gateway de
-- pagamento): a igreja cadastra a chave Pix em Configuracoes, o sistema
-- monta o QR code/copia-e-cola localmente (ver Igrejas\Core\PixEstatico)
-- e o doador confirma manualmente que fez o Pix (nao ha webhook - o
-- Banco Central nao notifica terceiros de transferencias por chave).

ALTER TABLE configuracoes_igreja
    ADD COLUMN pix_chave VARCHAR(140) NULL AFTER estado,
    ADD COLUMN pix_nome_beneficiario VARCHAR(25) NULL AFTER pix_chave;

CREATE TABLE IF NOT EXISTS financeiro_doacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_doador VARCHAR(150) NULL,
    categoria_id INT UNSIGNED NULL,
    valor DECIMAL(10,2) NOT NULL,
    mensagem VARCHAR(255) NULL,
    txid VARCHAR(25) NOT NULL UNIQUE,
    status ENUM('pendente', 'confirmada') NOT NULL DEFAULT 'pendente',
    lancamento_id INT UNSIGNED NULL,
    confirmada_em TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY financeiro_doacoes_status_index (status),
    CONSTRAINT financeiro_doacoes_categoria_id_foreign
        FOREIGN KEY (categoria_id) REFERENCES financeiro_categorias (id) ON DELETE SET NULL,
    CONSTRAINT financeiro_doacoes_lancamento_id_foreign
        FOREIGN KEY (lancamento_id) REFERENCES financeiro_lancamentos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
