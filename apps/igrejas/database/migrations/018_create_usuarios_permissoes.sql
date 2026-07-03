-- KADOSYS Igrejas - Migracao 018
-- Modulo Usuarios e Permissoes: ate aqui so existia 1 usuario admin por
-- igreja (criado no provisionamento). Agora um admin pode convidar mais
-- usuarios, cada um com um papel:
--   - 'admin': acesso total, inclusive gestao de usuarios/permissoes e
--     configuracoes/plano da igreja.
--   - 'usuario': acesso a todos os modulos liberados pelo plano da
--     igreja (mesma regra que um admin), EXCETO Usuarios, Permissoes e
--     Configuracoes - a nao ser que o admin restrinja ainda mais via
--     Permissoes (exclusivo do plano Premium/top - ver
--     Igrejas\Models\Plano::MODULO_MINIMO['permissoes']).
--
-- user_modulos funciona como uma lista de permissao (allow-list): se um
-- usuario NAO tem nenhuma linha aqui, ele usa o padrao (todos os
-- modulos do plano). Se tem pelo menos uma linha, so os modulos
-- listados ficam liberados pra ele - ver Igrejas\Models\User::podeAcessarModulo().

ALTER TABLE users
    MODIFY COLUMN role ENUM('admin', 'usuario') NOT NULL DEFAULT 'admin';

CREATE TABLE IF NOT EXISTS user_modulos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    modulo_slug VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_modulos_unique (user_id, modulo_slug),
    CONSTRAINT user_modulos_user_id_foreign
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
