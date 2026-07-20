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
    logo_path VARCHAR(255) NULL,
    cor_primaria VARCHAR(7) NULL,
    plano ENUM('essencial', 'premium', 'enterprise') NOT NULL DEFAULT 'essencial',
    metodo_pagamento ENUM('cartao', 'pix', 'trial') NOT NULL DEFAULT 'trial',
    mp_preapproval_id VARCHAR(64) NULL,
    trial_expira_em DATETIME NULL,
    proximo_vencimento DATE NULL,
    plano_agendado VARCHAR(20) NULL,
    fidelidade_pontos_por_real DECIMAL(6, 2) NULL,
    ultimo_acesso_em DATETIME NULL,
    status ENUM('pendente', 'ativo', 'suspenso') NOT NULL DEFAULT 'pendente',
    cancelado_em DATETIME NULL,
    modo_atendimento ENUM('agendamento', 'fila') NOT NULL DEFAULT 'agendamento',
    pix_chave VARCHAR(140) NULL,
    pix_nome_beneficiario VARCHAR(25) NULL,
    pix_cidade VARCHAR(15) NULL,
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

-- Recuperacao de senha ("esqueci minha senha", ver Barbearias\Controllers\AuthController).
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY password_resets_email_index (email)
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

-- Modo de atendimento por fila (ordem de chegada, sem horario marcado) -
-- alternativa ao Agendamento (barbearias.modo_atendimento escolhe qual
-- dos dois esta em uso). Uma linha por pessoa na fila - "entrou_em"
-- define a ordem (FIFO). Cliente pode nao ter cadastro nenhum em
-- "clientes" (fila e pensada pra ser rapida, sem exigir cadastro
-- completo), por isso guarda nome e telefone direto aqui.
CREATE TABLE IF NOT EXISTS fila_atendimento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NULL,
    nome VARCHAR(150) NOT NULL,
    telefone VARCHAR(20) NULL,
    status ENUM('aguardando', 'em_atendimento', 'atendido', 'cancelado') NOT NULL DEFAULT 'aguardando',
    entrou_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    chamado_em TIMESTAMP NULL,
    atendido_em TIMESTAMP NULL,
    KEY fila_atendimento_barbearia_id_status_index (barbearia_id, status),
    CONSTRAINT fila_atendimento_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT fila_atendimento_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE SET NULL
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
    cpf VARCHAR(14) NULL,
    data_nascimento DATE NULL,
    pontos_fidelidade INT UNSIGNED NOT NULL DEFAULT 0,
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
    lembrete_enviado_em DATETIME NULL,
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

-- Produtos vendidos avulsamente (nao vinculados a um agendamento), com
-- controle simples de estoque - uma venda da baixa em estoque_atual e
-- gera um lancamento de receita (ver produto_id/quantidade abaixo, em
-- Barbearias\Controllers\ProdutoController::vender).
CREATE TABLE IF NOT EXISTS produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    preco DECIMAL(10, 2) NOT NULL,
    estoque_atual INT NOT NULL DEFAULT 0,
    estoque_minimo INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY produtos_barbearia_id_index (barbearia_id),
    CONSTRAINT produtos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financeiro_lancamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    caixa_id INT UNSIGNED NULL,
    agendamento_id INT UNSIGNED NULL,
    produto_id INT UNSIGNED NULL,
    quantidade INT UNSIGNED NULL,
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
    CONSTRAINT financeiro_lancamentos_produto_id_foreign
        FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE SET NULL,
    CONSTRAINT financeiro_lancamentos_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de pagamento de comissao a um profissional - gera uma
-- despesa no caixa aberto (financeiro_lancamentos) e guarda o
-- comprovante anexado, alem do periodo exato pago (pra nao deixar
-- pagar a mesma comissao duas vezes).
CREATE TABLE IF NOT EXISTS comissao_pagamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NOT NULL,
    financeiro_lancamento_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fim DATE NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    comprovante_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY comissao_pagamentos_barbearia_id_index (barbearia_id),
    KEY comissao_pagamentos_profissional_periodo_index (profissional_id, periodo_inicio, periodo_fim),
    CONSTRAINT comissao_pagamentos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT comissao_pagamentos_profissional_id_foreign
        FOREIGN KEY (profissional_id) REFERENCES profissionais (id) ON DELETE CASCADE,
    CONSTRAINT comissao_pagamentos_lancamento_id_foreign
        FOREIGN KEY (financeiro_lancamento_id) REFERENCES financeiro_lancamentos (id) ON DELETE CASCADE,
    CONSTRAINT comissao_pagamentos_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Programa de fidelidade: pontos por real gasto (barbearias.fidelidade_pontos_por_real,
-- NULL = desativado), resgatados pelas recompensas cadastradas aqui.
CREATE TABLE IF NOT EXISTS fidelidade_recompensas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    pontos_necessarios INT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY fidelidade_recompensas_barbearia_id_index (barbearia_id),
    CONSTRAINT fidelidade_recompensas_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extrato: cada ganho (atendimento pago) ou resgate (recompensa
-- trocada) vira uma linha aqui, pra dar historico auditavel do saldo
-- de pontos de cada cliente.
CREATE TABLE IF NOT EXISTS fidelidade_movimentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    tipo ENUM('ganho', 'resgate') NOT NULL,
    pontos INT NOT NULL,
    agendamento_id INT UNSIGNED NULL,
    recompensa_id INT UNSIGNED NULL,
    descricao VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY fidelidade_movimentos_barbearia_id_index (barbearia_id),
    KEY fidelidade_movimentos_cliente_id_index (cliente_id),
    CONSTRAINT fidelidade_movimentos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT fidelidade_movimentos_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE,
    CONSTRAINT fidelidade_movimentos_agendamento_id_foreign
        FOREIGN KEY (agendamento_id) REFERENCES agendamentos (id) ON DELETE SET NULL,
    CONSTRAINT fidelidade_movimentos_recompensa_id_foreign
        FOREIGN KEY (recompensa_id) REFERENCES fidelidade_recompensas (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assinaturas de cliente: pacotes pre-pagos de N atendimentos por mes
-- (ex.: "4 cortes por mes por R$120"). A cobranca mensal em si fica
-- FORA do sistema (mesma logica manual do resto do financeiro) - o
-- que o app controla e o consumo: quantos atendimentos o cliente ja
-- usou no ciclo atual (ver Barbearias\Controllers\AssinaturaClienteController).
CREATE TABLE IF NOT EXISTS assinatura_planos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    preco DECIMAL(10, 2) NOT NULL,
    atendimentos_por_mes INT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY assinatura_planos_barbearia_id_index (barbearia_id),
    CONSTRAINT assinatura_planos_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "data_inicio" ancora o ciclo mensal (renova a cada mes a partir
-- dessa data, sem precisar de cron pra "resetar" nada - o saldo
-- disponivel e sempre calculado nas consultas, contando os consumos
-- desde o inicio do ciclo atual).
CREATE TABLE IF NOT EXISTS assinaturas_clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbearia_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    plano_id INT UNSIGNED NOT NULL,
    status ENUM('ativa', 'cancelada') NOT NULL DEFAULT 'ativa',
    data_inicio DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY assinaturas_clientes_barbearia_id_index (barbearia_id),
    KEY assinaturas_clientes_cliente_id_index (cliente_id),
    CONSTRAINT assinaturas_clientes_barbearia_id_foreign
        FOREIGN KEY (barbearia_id) REFERENCES barbearias (id) ON DELETE CASCADE,
    CONSTRAINT assinaturas_clientes_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE,
    CONSTRAINT assinaturas_clientes_plano_id_foreign
        FOREIGN KEY (plano_id) REFERENCES assinatura_planos (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Um consumo por agendamento (chave unica, mesmo padrao de
-- financeiro_lancamentos.agendamento_id) - marca que aquele
-- atendimento foi "pago" usando o pacote da assinatura, sem gerar
-- lancamento financeiro (a cobranca da mensalidade e feita fora do
-- sistema).
CREATE TABLE IF NOT EXISTS assinatura_consumos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assinatura_id INT UNSIGNED NOT NULL,
    agendamento_id INT UNSIGNED NOT NULL,
    data_consumo DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY assinatura_consumos_agendamento_id_unique (agendamento_id),
    KEY assinatura_consumos_assinatura_id_index (assinatura_id),
    CONSTRAINT assinatura_consumos_assinatura_id_foreign
        FOREIGN KEY (assinatura_id) REFERENCES assinaturas_clientes (id) ON DELETE CASCADE,
    CONSTRAINT assinatura_consumos_agendamento_id_foreign
        FOREIGN KEY (agendamento_id) REFERENCES agendamentos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Avisos do dono da plataforma (KADOSYS) para as barbearias cadastradas,
-- publicados pelo Super Admin (apps/superadmin) e mostrados no sino de
-- notificacoes do painel - ver Barbearias\Models\BarbeariaAviso. Uma
-- linha ativa por vez, visivel pra todas as barbearias (banco ja
-- compartilhado, sem precisar de coluna barbearia_id aqui).
CREATE TABLE IF NOT EXISTS barbearia_avisos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mensagem TEXT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY barbearia_avisos_ativo_index (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
