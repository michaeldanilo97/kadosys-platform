-- Avisos do dono da plataforma (KADOSYS) para as barbearias cadastradas
-- - ex.: manutencao programada, novo recurso disponivel. Publicado pelo
-- Super Admin (apps/superadmin) e mostrado no sino de notificacoes do
-- painel de toda barbearia (ver Barbearias\Models\BarbeariaAviso e
-- resources/views/layouts/dashboard.php).
--
-- Diferente do Igrejas (banco isolado por igreja, avisos guardados num
-- banco CENTRAL separado), o Barbearias ja usa um unico banco
-- compartilhado por todas as barbearias - entao essa tabela mora aqui
-- mesmo, sem precisar de nenhum banco a parte: uma linha ativa e visivel
-- pra todo mundo, mesma semantica de Igrejas\Models\PlataformaAviso::publicar()
-- (publicar um novo aviso encerra o anterior automaticamente).
CREATE TABLE IF NOT EXISTS barbearia_avisos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mensagem TEXT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY barbearia_avisos_ativo_index (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
