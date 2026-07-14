-- KADOSYS Igrejas - Migracao 045
-- RODAR NO BANCO DE CADA IGREJA (nao e no banco central)
--
-- Permissoes por modulo deixam de ser so "tem acesso ou nao" - cada
-- modulo liberado pra um usuario agora tem um nivel: "visualizar" (so
-- ve a tela, nao consegue salvar/excluir nada) ou "editar" (acesso
-- completo, como sempre foi). Tambem cria o "perfil padrao" que a
-- igreja configura em Configuracoes: o conjunto de modulos/niveis que
-- um novo acesso de usuario ja recebe automaticamente, sem o admin
-- precisar configurar Permissoes na mao pra cada pessoa.

ALTER TABLE user_modulos
    ADD COLUMN nivel ENUM('visualizar', 'editar') NOT NULL DEFAULT 'editar' AFTER modulo_slug;

CREATE TABLE IF NOT EXISTS permissoes_padrao (
    modulo_slug VARCHAR(50) NOT NULL PRIMARY KEY,
    nivel ENUM('visualizar', 'editar') NOT NULL DEFAULT 'visualizar',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Perfil padrao inicial: acesso "seguro" que nao compromete
-- informacao sensivel (Financeiro, Patrimonio, Comunicacao,
-- Relatorios, Projecao ficam de fora - precisam ser liberados na mao)
-- e deixa os modulos de uso geral do dia a dia como "so visualizar"
-- (a igreja pode trocar pra "editar" a qualquer momento em
-- Configuracoes > Permissoes padrao).
INSERT IGNORE INTO permissoes_padrao (modulo_slug, nivel) VALUES
    ('agenda', 'visualizar'),
    ('equipe', 'visualizar'),
    ('cultos', 'visualizar'),
    ('ministerios', 'visualizar'),
    ('grupos', 'visualizar'),
    ('membros', 'visualizar'),
    ('playbacks', 'visualizar');
