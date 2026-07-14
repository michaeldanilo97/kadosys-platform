-- KADOSYS Igrejas - Migracao 047
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Aba "Documentos" da tela de perfil do membro: upload de arquivos
-- (RG, comprovante, certidao, foto de documento etc.) vinculados ao
-- membro. Mesmo padrao de storage em disco usado por Playbacks -
-- so o caminho do arquivo fica no banco, o arquivo em si vai pra
-- public/uploads/membros/{tenant}/.

CREATE TABLE IF NOT EXISTS membro_documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    membro_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    arquivo_path VARCHAR(255) NOT NULL,
    tamanho_bytes INT UNSIGNED NOT NULL,
    enviado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT membro_documentos_membro_id_foreign
        FOREIGN KEY (membro_id) REFERENCES membros (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
