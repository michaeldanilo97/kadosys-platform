-- KADOSYS Food - Migration 004
-- Rodar uma unica vez em restaurantes ja instalados antes desta
-- migration (instalacoes novas ja recebem isso direto pelo install.sql).

-- Cliente da loja. "endereco" e texto livre (sem CEP/autofill - isso
-- foi construido em outros apps so pra formularios PUBLICOS de
-- autocadastro, que o Food nao tem; aqui e sempre a equipe cadastrando).
-- Ticket medio/total gasto/frequencia/ultimo pedido NAO ficam guardados
-- aqui - sao calculados via query sobre `pedidos` (ver
-- Cliente::estatisticas()), pra nunca ficarem desatualizados.
CREATE TABLE IF NOT EXISTS clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    telefone VARCHAR(20) NULL,
    whatsapp VARCHAR(20) NULL,
    aniversario DATE NULL,
    endereco TEXT NULL,
    observacoes TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY clientes_restaurante_id_index (restaurante_id),
    CONSTRAINT clientes_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pedido. "status" ja nasce com os 6 valores do fluxo de producao
-- completo (Recebido/Em preparo/Finalizado/Saiu para entrega/Entregue/
-- Cancelado) descrito no spec original, mesmo a tela de producao (TV
-- da cozinha) so chegando na Fase 6 - assim a coluna nao precisa de
-- outra migration depois. NESTA fase, o unico avanco de status
-- disponivel na tela e via Pedido::finalizar() (recebido -> em_preparo,
-- ver comentario no Model) - os demais avancos (em_preparo ->
-- finalizado -> saiu_para_entrega -> entregue) sao expostos pela tela
-- de Producao na Fase 6.
-- "cliente_id" e nullable e ON DELETE SET NULL (pedido de balcao sem
-- cliente identificado e comum, e excluir um cliente nao deveria
-- apagar o historico de pedidos dele).
-- "subtotal"/"valor_total" sao cache (soma dos itens, e soma - desconto
-- respectivamente) - ver Pedido::recalcularValores(), chamado sempre
-- que um item e adicionado/removido.
CREATE TABLE IF NOT EXISTS pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NULL,
    origem ENUM('balcao', 'whatsapp', 'ifood_manual', 'delivery_proprio') NOT NULL,
    status ENUM('recebido', 'em_preparo', 'finalizado', 'saiu_para_entrega', 'entregue', 'cancelado') NOT NULL DEFAULT 'recebido',
    forma_pagamento ENUM('dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'outro') NOT NULL DEFAULT 'dinheiro',
    endereco_entrega TEXT NULL,
    cupom VARCHAR(60) NULL,
    desconto DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    valor_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    observacoes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY pedidos_restaurante_id_index (restaurante_id),
    KEY pedidos_cliente_id_index (cliente_id),
    CONSTRAINT pedidos_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE,
    CONSTRAINT pedidos_cliente_id_foreign
        FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Item de pedido. "observacao" cobre tanto observacao quanto
-- "adicionais" em texto livre (ex.: "+ bacon, sem cebola") - nao existe
-- catalogo separado de adicionais com preco proprio, o que exigiria uma
-- estrutura de precificacao propria nao detalhada no pedido original.
-- "preco_unitario" e copiado do preco do produto no canal da origem do
-- pedido no momento em que o item e adicionado (editavel manualmente
-- na hora, ex. pra aplicar um preco promocional pontual).
CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NOT NULL,
    quantidade INT UNSIGNED NOT NULL,
    preco_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    observacao VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY pedido_itens_pedido_id_index (pedido_id),
    KEY pedido_itens_produto_id_index (produto_id),
    CONSTRAINT pedido_itens_pedido_id_foreign
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE,
    CONSTRAINT pedido_itens_produto_id_foreign
        FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lancamento financeiro minimo - criado automaticamente por
-- Pedido::finalizar() (uma receita por pedido confirmado). Ainda nao
-- tem tela propria (dashboard/CRUD/relatorios ficam pra Fase 7 -
-- Financeiro completo); existe desde ja so pra guardar o historico
-- correto desde o primeiro pedido confirmado.
CREATE TABLE IF NOT EXISTS financeiro_lancamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    pedido_id INT UNSIGNED NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    categoria VARCHAR(60) NULL,
    forma_pagamento VARCHAR(30) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    descricao VARCHAR(255) NULL,
    data_lancamento DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY financeiro_lancamentos_restaurante_id_index (restaurante_id),
    UNIQUE KEY financeiro_lancamentos_pedido_id_unique (pedido_id),
    CONSTRAINT financeiro_lancamentos_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE,
    CONSTRAINT financeiro_lancamentos_pedido_id_foreign
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
