-- KADOSYS Academias - Schema inicial (Fase 1: esqueleto + billing +
-- backbone operacional). RODAR UMA UNICA VEZ no banco
-- kadosys1_academias (banco UNICO e compartilhado entre todas as
-- academias - mesmo padrao ja usado pelo KADOSYS Barbearias, diferente
-- do KADOSYS Igrejas que usa um banco por cliente). Toda tabela de
-- negocio (exceto a propria `academias`) tem uma coluna academia_id que
-- isola os dados de cada academia - todo Model PRECISA filtrar por ela
-- em toda query, ja que o banco nao faz esse isolamento sozinho.

-- "status" comeca 'pendente' pra quem escolhe Pix/cartao (so vira
-- 'ativo' quando o webhook do Mercado Pago confirma o pagamento) e ja
-- nasce 'ativo' pra quem escolhe trial (sem cobranca nenhuma nos
-- primeiros dias, ver Academias\Models\Plano::TRIAL_DIAS). "ativar" e
-- so um UPDATE nesta mesma linha, sem infraestrutura de provisionamento
-- (mesmo raciocinio ja usado em barbearias). "qr_checkin_token" so e
-- usado a partir da Fase 3 (check-in/checkout), ja incluido aqui pra
-- nao precisar de outra migration so pra essa coluna.
CREATE TABLE IF NOT EXISTS academias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    telefone VARCHAR(20) NULL,
    documento_tipo ENUM('cpf', 'cnpj') NOT NULL DEFAULT 'cpf',
    documento VARCHAR(14) NOT NULL DEFAULT '',
    razao_social VARCHAR(190) NULL,
    logo_path VARCHAR(255) NULL,
    cor_primaria VARCHAR(7) NULL,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL DEFAULT 'essencial',
    metodo_pagamento ENUM('cartao', 'pix', 'trial') NOT NULL DEFAULT 'trial',
    mp_preapproval_id VARCHAR(64) NULL,
    trial_expira_em DATETIME NULL,
    proximo_vencimento DATE NULL,
    plano_agendado VARCHAR(20) NULL,
    ultimo_acesso_em DATETIME NULL,
    status ENUM('pendente', 'ativo', 'suspenso') NOT NULL DEFAULT 'pendente',
    cancelado_em DATETIME NULL,
    qr_checkin_token VARCHAR(64) NULL,
    pix_chave VARCHAR(140) NULL,
    pix_nome_beneficiario VARCHAR(25) NULL,
    pix_cidade VARCHAR(15) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY academias_slug_unique (slug),
    UNIQUE KEY academias_qr_checkin_token_unique (qr_checkin_token),
    KEY academias_documento_index (documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cobranca Pix avulsa - cobre tanto o PRIMEIRO pagamento (cadastro
-- novo) quanto cada renovacao mensal seguinte (gerada por
-- cron/gerar_faturas_pix.php). Cartao nao gera linha aqui: a cobranca
-- recorrente e feita pelo proprio Mercado Pago via preapproval (ver
-- academias.mp_preapproval_id). Mesmo padrao de barbearia_faturas.
CREATE TABLE IF NOT EXISTS academia_faturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL,
    tipo ENUM('renovacao', 'upgrade_proporcional') NOT NULL DEFAULT 'renovacao',
    valor DECIMAL(10, 2) NOT NULL,
    mp_payment_id VARCHAR(64) NULL,
    pix_qr_code TEXT NULL,
    pix_qr_code_base64 MEDIUMTEXT NULL,
    status ENUM('pendente', 'paga', 'expirada', 'cancelada') NOT NULL DEFAULT 'pendente',
    vencimento DATETIME NOT NULL,
    pago_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY academia_faturas_mp_payment_id_unique (mp_payment_id),
    KEY academia_faturas_academia_id_index (academia_id),
    CONSTRAINT academia_faturas_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users.email e unico GLOBALMENTE (nao por academia) - cada conta de
-- acesso da equipe (dono/recepcao/professor com login) pertence a
-- exatamente uma academia.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'usuario') NOT NULL DEFAULT 'admin',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY users_email_unique (email),
    KEY users_academia_id_index (academia_id),
    CONSTRAINT users_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recuperacao de senha ("esqueci minha senha", ver Academias\Controllers\AuthController).
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY password_resets_email_index (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Filiais/unidades fisicas da academia. Toda academia ja nasce com uma
-- unidade "principal" (ver CadastroController/seed_admin.php); a UI so
-- mostra seletor de unidade quando ha 2 ou mais.
CREATE TABLE IF NOT EXISTS unidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    endereco VARCHAR(255) NULL,
    cidade VARCHAR(100) NULL,
    estado CHAR(2) NULL,
    cep VARCHAR(9) NULL,
    telefone VARCHAR(20) NULL,
    whatsapp VARCHAR(20) NULL,
    principal TINYINT(1) NOT NULL DEFAULT 0,
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unidades_academia_id_slug_unique (academia_id, slug),
    KEY unidades_academia_id_index (academia_id),
    CONSTRAINT unidades_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catalogo de planos de matricula (ex.: Mensal, Trimestral, Anual) que
-- a academia oferece pros proprios alunos - independente dos planos de
-- assinatura da academia COM a Kadosys (tabela `academias.plano`).
CREATE TABLE IF NOT EXISTS planos_matricula (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    preco DECIMAL(10, 2) NOT NULL,
    duracao_dias INT UNSIGNED NOT NULL DEFAULT 30,
    descricao TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY planos_matricula_academia_id_index (academia_id),
    CONSTRAINT planos_matricula_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Professores/personal trainers. "percentual_comissao" fica reservado
-- pra uma eventual comissao por personal training avulso (mesmo padrao
-- ja usado em barbearias.profissionais), nao usado ainda na Fase 1.
CREATE TABLE IF NOT EXISTS professores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    telefone VARCHAR(20) NULL,
    especialidade VARCHAR(100) NULL,
    foto_path VARCHAR(255) NULL,
    percentual_comissao DECIMAL(5, 2) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY professores_academia_id_index (academia_id),
    CONSTRAINT professores_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alunos/matriculados. "password" comeca NULL (conta do painel do
-- aluno criada depois, self-service). As colunas de gamificacao
-- (streak/pontos) e "ultimo_checkin_dia" so passam a ser escritas a
-- partir da Fase 3 (check-in/checkout) mas ja entram no schema aqui
-- pra nao precisar de outra migration so pra isso.
CREATE TABLE IF NOT EXISTS alunos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    telefone VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    cpf VARCHAR(14) NULL,
    data_nascimento DATE NULL,
    foto_path VARCHAR(255) NULL,
    password VARCHAR(255) NULL,
    plano_matricula_id INT UNSIGNED NULL,
    matricula_inicio DATE NULL,
    matricula_vencimento DATE NULL,
    status ENUM('ativo', 'inativo', 'suspenso') NOT NULL DEFAULT 'ativo',
    objetivo VARCHAR(190) NULL,
    observacoes_saude TEXT NULL,
    streak_atual INT UNSIGNED NOT NULL DEFAULT 0,
    streak_recorde INT UNSIGNED NOT NULL DEFAULT 0,
    pontos_frequencia INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_checkin_dia DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY alunos_academia_id_index (academia_id),
    KEY alunos_plano_matricula_id_index (plano_matricula_id),
    CONSTRAINT alunos_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE,
    CONSTRAINT alunos_plano_matricula_id_foreign
        FOREIGN KEY (plano_matricula_id) REFERENCES planos_matricula (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Avisos do dono da plataforma (KADOSYS) para as academias cadastradas,
-- publicados pelo Super Admin (apps/superadmin) e mostrados no sino de
-- notificacoes do painel - ver Academias\Models\AcademiaAviso. Uma
-- linha ativa por vez, visivel pra todas as academias (banco ja
-- compartilhado, sem precisar de coluna academia_id aqui).
CREATE TABLE IF NOT EXISTS academia_avisos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mensagem TEXT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY academia_avisos_ativo_index (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 001 (caixas + financeiro_lancamentos) - ja incluida aqui
-- pra instalacoes novas partirem direto com o modulo Financeiro.
-- (instalacoes novas ja recebem isso direto pelo install.sql).

-- Sessao de caixa (abertura/fechamento) do dia. So pode existir UM
-- caixa aberto por vez por academia.
CREATE TABLE IF NOT EXISTS caixas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NULL,
    status ENUM('aberto', 'fechado') NOT NULL DEFAULT 'aberto',
    valor_abertura DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    valor_fechamento_informado DECIMAL(10, 2) NULL,
    observacoes_abertura TEXT NULL,
    observacoes_fechamento TEXT NULL,
    aberto_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechado_em TIMESTAMP NULL,
    KEY caixas_academia_id_index (academia_id),
    KEY caixas_status_index (status),
    CONSTRAINT caixas_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE,
    CONSTRAINT caixas_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lancamento financeiro (receita ou despesa). "aluno_id" opcional -
-- preenchido quando o lancamento e o pagamento de uma mensalidade
-- (categoria "mensalidade"), pra aparecer no historico do aluno mais
-- pra frente.
CREATE TABLE IF NOT EXISTS financeiro_lancamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    caixa_id INT UNSIGNED NULL,
    aluno_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    categoria VARCHAR(80) NOT NULL,
    forma_pagamento ENUM('dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'outro') NOT NULL DEFAULT 'outro',
    valor DECIMAL(10, 2) NOT NULL,
    descricao VARCHAR(255) NULL,
    data_lancamento DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY financeiro_lancamentos_academia_id_index (academia_id),
    KEY financeiro_lancamentos_caixa_id_index (caixa_id),
    KEY financeiro_lancamentos_aluno_id_index (aluno_id),
    KEY financeiro_lancamentos_data_lancamento_index (data_lancamento),
    CONSTRAINT financeiro_lancamentos_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE,
    CONSTRAINT financeiro_lancamentos_caixa_id_foreign
        FOREIGN KEY (caixa_id) REFERENCES caixas (id) ON DELETE SET NULL,
    CONSTRAINT financeiro_lancamentos_aluno_id_foreign
        FOREIGN KEY (aluno_id) REFERENCES alunos (id) ON DELETE SET NULL,
    CONSTRAINT financeiro_lancamentos_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 002 (academia_checkins) - ja incluida aqui pra
-- instalacoes novas partirem direto com o modulo de check-in.
-- (instalacoes novas ja recebem isso direto pelo install.sql).
--
-- As colunas de gamificacao (streak_atual, streak_recorde,
-- pontos_frequencia, ultimo_checkin_dia) e qr_checkin_token ja existem
-- desde a Fase 1 (install.sql original) - so faltava esta tabela de
-- registro de check-in/checkout.

-- Registro de check-in/checkout via QR fixo. "saida_em" NULL = aluno
-- ainda esta dentro da academia (check-in em aberto). Cada academia so
-- pode ter, no maximo, um check-in em aberto por aluno de cada vez
-- (garantido em codigo, ver Academias\Models\AcademiaCheckin::emAberto).
CREATE TABLE IF NOT EXISTS academia_checkins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academia_id INT UNSIGNED NOT NULL,
    aluno_id INT UNSIGNED NOT NULL,
    entrada_em DATETIME NOT NULL,
    saida_em DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY academia_checkins_academia_id_index (academia_id),
    KEY academia_checkins_aluno_id_index (aluno_id),
    KEY academia_checkins_aluno_saida_index (aluno_id, saida_em),
    CONSTRAINT academia_checkins_academia_id_foreign
        FOREIGN KEY (academia_id) REFERENCES academias (id) ON DELETE CASCADE,
    CONSTRAINT academia_checkins_aluno_id_foreign
        FOREIGN KEY (aluno_id) REFERENCES alunos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
