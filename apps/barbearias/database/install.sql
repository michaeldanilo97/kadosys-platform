-- KADOSYS Barbearias - Schema inicial
-- RODAR UMA UNICA VEZ no banco kadosys1_barbearias (banco UNICO e
-- compartilhado entre todas as barbearias - diferente do KADOSYS
-- Igrejas, aqui NAO ha um banco por cliente). Toda tabela de negocio
-- (exceto a propria `barbearias`) tem uma coluna barbearia_id que
-- isola os dados de cada barbearia - todo Model PRECISA filtrar por
-- ela em toda query, ja que o banco nao faz esse isolamento sozinho.

-- "status" comeca 'pendente' pra quem escolhe Pix/cartao (so vira
-- 'ativo' quando o webhook do Mercado Pago confirma o pagamento) e ja
-- nasce 'ativo' pra quem escolhe trial (sem cobranca nenhuma nos
-- primeiros dias, ver Barbearias\Models\Plano::TRIAL_DIAS). Diferente
-- do KADOSYS Igrejas, aqui NAO ha banco/subdominio pra provisionar -
-- "ativar" e so um UPDATE nesta mesma linha, sem infraestrutura
-- nenhuma de por meio, entao nao precisa de uma tabela separada de
-- "tentativas de cadastro" (plataforma_provisionamentos de Igrejas) -
-- a propria linha em barbearias, com status 'pendente', ja cumpre
-- esse papel.
CREATE TABLE IF NOT EXISTS barbearias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    telefone VARCHAR(20) NULL,
    documento_tipo ENUM('cpf', 'cnpj') NOT NULL DEFAULT 'cpf',
    documento VARCHAR(14) NOT NULL DEFAULT '',
    razao_social VARCHAR(190) NULL,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL DEFAULT 'essencial',
    metodo_pagamento ENUM('cartao', 'pix', 'trial') NOT NULL DEFAULT 'trial',
    mp_preapproval_id VARCHAR(64) NULL,
    trial_expira_em DATETIME NULL,
    proximo_vencimento DATE NULL,
    plano_agendado VARCHAR(20) NULL,
    ultimo_acesso_em DATETIME NULL,
    status ENUM('pendente', 'ativo', 'suspenso') NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY barbearias_slug_unique (slug),
    KEY barbearias_documento_index (documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cobranca Pix avulsa - cobre tanto o PRIMEIRO pagamento (cadastro
-- novo) quanto cada renovacao mensal seguinte (gerada por
-- cron/gerar_faturas_pix.php), sem distincao entre as duas - ao
-- contrario de Igrejas (que separa "primeiro pagamento" em
-- plataforma_provisionamentos de "renovacao" em plataforma_faturas),
-- aqui a barbearia ja existe com status 'pendente' desde o primeiro
-- pagamento, entao toda cobranca Pix pode viver nesta unica tabela.
-- Cartao nao gera linha aqui: a cobranca recorrente e feita pelo
-- proprio Mercado Pago via preapproval (ver barbearias.mp_preapproval_id).
CREATE TABLE IF NOT EXISTS barbearia_faturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
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
    UNIQUE KEY barbearia_faturas_mp_payment_id_unique (mp_payment_id),
    KEY barbearia_faturas_barbearia_id_index (barbearia_id),
    CONSTRAINT barbearia_faturas_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users.email e unico GLOBALMENTE (nao por barbearia) - cada conta de
-- acesso pertence a exatamente uma barbearia, e o login resolve direto
-- pra qual sem precisar de uma etapa de selecao (diferente do KADOSYS
-- Igrejas, que tem varios bancos e por isso pode ter o mesmo e-mail em
-- mais de um).
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'usuario') NOT NULL DEFAULT 'admin',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY users_email_unique (email),
    KEY users_barbearia_id_index (barbearia_id),
    CONSTRAINT users_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "dias_atendimento" e uma lista separada por virgula dos dias da
-- semana que o profissional atende, no formato do PHP DateTime::format('w')
-- (0 = domingo ... 6 = sabado), ex.: "1,2,3,4,5" pra segunda a sexta.
-- "horario_inicio"/"horario_fim" delimitam o expediente - usados pra
-- calcular os horarios disponiveis no agendamento publico (ver
-- Barbearias\Controllers\AgendamentoPublicoController).
CREATE TABLE IF NOT EXISTS profissionais (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    especialidade VARCHAR(150) NULL,
    email VARCHAR(150) NULL,
    telefone VARCHAR(20) NULL,
    foto_path VARCHAR(255) NULL,
    dias_atendimento VARCHAR(20) NULL,
    horario_inicio TIME NULL,
    horario_fim TIME NULL,
    percentual_comissao DECIMAL(5,2) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY profissionais_barbearia_id_index (barbearia_id),
    CONSTRAINT profissionais_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Unidade fisica (filial) de uma barbearia. Toda barbearia nasce com
-- uma unidade "principal" automatica (ver Barbearias\Models\Unidade::criarPrincipal,
-- chamado no cadastro publico) - quem nunca precisou de mais de um
-- endereco nao ve tela nenhuma nova, so quem cria uma segunda unidade
-- passa a ver os seletores de unidade espalhados pelo painel.
CREATE TABLE IF NOT EXISTS unidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
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
    UNIQUE KEY unidades_barbearia_id_slug_unique (barbearia_id, slug),
    KEY unidades_barbearia_id_index (barbearia_id),
    CONSTRAINT unidades_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pivot: um profissional pode atender em uma ou mais unidades.
CREATE TABLE IF NOT EXISTS profissional_unidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profissional_id INT UNSIGNED NOT NULL,
    unidade_id INT UNSIGNED NOT NULL,
    UNIQUE KEY profissional_unidades_unique (profissional_id, unidade_id),
    KEY profissional_unidades_unidade_id_index (unidade_id),
    CONSTRAINT profissional_unidades_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE,
    CONSTRAINT profissional_unidades_unidade_id_foreign
        FOREIGN KEY (unidade_id) REFERENCES unidades (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bloqueios de agenda: qualquer periodo em que um profissional NAO
-- atende, alem do expediente normal (dias_atendimento/horario_inicio/
-- horario_fim la em cima) - bloqueio manual pontual, ferias ou folga.
-- Sempre respeitado pelo calculo de horarios disponiveis do
-- agendamento publico (ver Barbearias\Controllers\AgendamentoPublicoController)
-- e validado ao criar/editar um agendamento pelo painel.
CREATE TABLE IF NOT EXISTS bloqueios_agenda (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    motivo VARCHAR(150) NULL,
    tipo ENUM('bloqueio', 'ferias', 'folga') NOT NULL DEFAULT 'bloqueio',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY bloqueios_agenda_barbearia_id_index (barbearia_id),
    KEY bloqueios_agenda_profissional_id_index (profissional_id),
    KEY bloqueios_agenda_periodo_index (data_inicio, data_fim),
    CONSTRAINT bloqueios_agenda_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT bloqueios_agenda_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE,
    CONSTRAINT bloqueios_agenda_periodo_check CHECK (data_fim > data_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fotos do trabalho de um profissional (antes/depois, cortes feitos)
-- mostradas na pagina publica de agendamento.
CREATE TABLE IF NOT EXISTS portfolio_fotos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NOT NULL,
    foto_path VARCHAR(255) NOT NULL,
    legenda VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY portfolio_fotos_barbearia_id_index (barbearia_id),
    KEY portfolio_fotos_profissional_id_index (profissional_id),
    CONSTRAINT portfolio_fotos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT portfolio_fotos_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    preco DECIMAL(10, 2) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY servicos_barbearia_id_index (barbearia_id),
    CONSTRAINT servicos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "password" comeca NULL - um cliente criado pelo agendamento publico
-- (Barbearias\Controllers\AgendamentoPublicoController) ou pelo
-- proprio painel da barbearia (Barbearias\Controllers\ClienteController)
-- ainda nao tem conta pra logar na area do cliente. So ganha senha
-- quando ele mesmo se cadastra em /minha-conta/{slug}/cadastro (ver
-- Barbearias\Controllers\ClienteAreaController) - se o telefone
-- informado ja tiver um registro (de um agendamento anterior sem
-- conta), a senha e adicionada a ele em vez de duplicar o cliente.
CREATE TABLE IF NOT EXISTS clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    telefone VARCHAR(20) NULL,
    email VARCHAR(150) NULL,
    data_nascimento DATE NULL,
    password VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY clientes_barbearia_id_index (barbearia_id),
    CONSTRAINT clientes_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "unidade_id" fica NULL quando a barbearia so tem uma unidade (o
-- caso comum) - o painel so pede pra escolher a unidade quando ha mais
-- de uma ativa (ver Barbearias\Models\Unidade::temMultiplasAtivas).
CREATE TABLE IF NOT EXISTS agendamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    unidade_id INT UNSIGNED NULL,
    profissional_id INT UNSIGNED NOT NULL,
    servico_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    data_hora DATETIME NOT NULL,
    status ENUM('agendado', 'concluido', 'cancelado') NOT NULL DEFAULT 'agendado',
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY agendamentos_barbearia_id_index (barbearia_id),
    KEY agendamentos_unidade_id_index (unidade_id),
    KEY agendamentos_data_hora_index (data_hora),
    CONSTRAINT agendamentos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT agendamentos_unidade_id_foreign
        FOREIGN KEY (unidade_id) REFERENCES unidades (id) ON DELETE SET NULL,
    CONSTRAINT agendamentos_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE,
    CONSTRAINT agendamentos_servico_id_foreign
        FOREIGN KEY (servico_id) REFERENCES servicos (id) ON DELETE CASCADE,
    CONSTRAINT agendamentos_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lista de espera: cliente entra na fila quando nao ha nenhum horario
-- livre no dia escolhido (agendamento publico) - a equipe ve a fila no
-- painel e entra em contato manualmente quando um horario abrir (nao
-- ha notificacao automatica).
CREATE TABLE IF NOT EXISTS lista_espera (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NULL,
    servico_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    data_desejada DATE NOT NULL,
    observacoes VARCHAR(255) NULL,
    status ENUM('aguardando', 'atendido', 'cancelado') NOT NULL DEFAULT 'aguardando',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY lista_espera_barbearia_id_index (barbearia_id),
    KEY lista_espera_status_index (status),
    CONSTRAINT lista_espera_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT lista_espera_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE SET NULL,
    CONSTRAINT lista_espera_servico_id_foreign
        FOREIGN KEY (servico_id) REFERENCES servicos (id) ON DELETE CASCADE,
    CONSTRAINT lista_espera_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Avaliacao que o cliente deixa depois de um atendimento concluido -
-- no maximo uma por agendamento (UNIQUE), pedida na propria area do
-- cliente (ver Barbearias\Controllers\ClienteAreaController).
CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    agendamento_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NOT NULL,
    nota TINYINT UNSIGNED NOT NULL,
    comentario TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY avaliacoes_agendamento_id_unique (agendamento_id),
    KEY avaliacoes_barbearia_id_index (barbearia_id),
    KEY avaliacoes_profissional_id_index (profissional_id),
    CONSTRAINT avaliacoes_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT avaliacoes_agendamento_id_foreign
        FOREIGN KEY (agendamento_id) REFERENCES agendamentos (id) ON DELETE CASCADE,
    CONSTRAINT avaliacoes_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE,
    CONSTRAINT avaliacoes_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE,
    CONSTRAINT avaliacoes_nota_check CHECK (nota BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modulo financeiro: caixa diario (abertura/fechamento) e lancamentos
-- de receita/despesa, com vinculo opcional a um agendamento (pagamento
-- de atendimento, registrado como um mini-PDV) ou a um caixa aberto.
CREATE TABLE IF NOT EXISTS caixas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NULL,
    status ENUM('aberto', 'fechado') NOT NULL DEFAULT 'aberto',
    valor_abertura DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    valor_fechamento_informado DECIMAL(10, 2) NULL,
    observacoes_abertura TEXT NULL,
    observacoes_fechamento TEXT NULL,
    aberto_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechado_em TIMESTAMP NULL,
    KEY caixas_barbearia_id_index (barbearia_id),
    KEY caixas_status_index (status),
    CONSTRAINT caixas_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT caixas_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financeiro_lancamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    caixa_id INT UNSIGNED NULL,
    agendamento_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    categoria VARCHAR(80) NOT NULL,
    forma_pagamento ENUM('dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'outro') NOT NULL DEFAULT 'outro',
    valor DECIMAL(10, 2) NOT NULL,
    descricao VARCHAR(255) NULL,
    data_lancamento DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY financeiro_lancamentos_barbearia_id_index (barbearia_id),
    KEY financeiro_lancamentos_caixa_id_index (caixa_id),
    KEY financeiro_lancamentos_data_lancamento_index (data_lancamento),
    UNIQUE KEY financeiro_lancamentos_agendamento_id_unique (agendamento_id),
    CONSTRAINT financeiro_lancamentos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT financeiro_lancamentos_caixa_id_foreign
        FOREIGN KEY (caixa_id) REFERENCES caixas (id) ON DELETE SET NULL,
    CONSTRAINT financeiro_lancamentos_agendamento_id_foreign
        FOREIGN KEY (agendamento_id) REFERENCES agendamentos (id) ON DELETE SET NULL,
    CONSTRAINT financeiro_lancamentos_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
