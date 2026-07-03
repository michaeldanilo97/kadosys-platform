-- KADOSYS Igrejas - Migracao 020
-- Tabela do modulo Comunicacao: avisos e comunicados centralizados
-- para membros e lideranca. O envio efetivo por e-mail/SMS/push fica
-- para uma proxima etapa (mesmo padrao ja adotado em outros pontos do
-- sistema, ex.: recuperacao de senha) - por enquanto e um mural
-- centralizado dentro do proprio painel.

CREATE TABLE IF NOT EXISTS comunicacao_avisos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    conteudo TEXT NOT NULL,
    publico_alvo ENUM('todos', 'lideranca') NOT NULL DEFAULT 'todos',
    status ENUM('rascunho', 'publicado', 'arquivado') NOT NULL DEFAULT 'rascunho',
    data_publicacao DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY comunicacao_avisos_status_index (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
