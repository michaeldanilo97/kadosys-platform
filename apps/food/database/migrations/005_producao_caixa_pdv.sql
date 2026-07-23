-- KADOSYS Food - Migration 005 (Fase 6: Producao + Caixa + PDV)
-- Rodar uma unica vez em restaurantes ja instalados antes desta
-- migration (instalacoes novas ja recebem isso direto pelo install.sql).

-- CORRECAO DE SEMANTICA: na Fase 5, "recebido" significava "pedido
-- ainda sendo montado, sem baixa de estoque" (ver Model Pedido). O
-- spec original da tela de Producao (Fase 6) usa "Recebido" como a
-- PRIMEIRA coluna do kanban da cozinha - ou seja, "a cozinha recebeu o
-- pedido confirmado", um estagio POSTERIOR a montagem. Pra separar as
-- duas coisas sem ambiguidade, entra o valor "montagem" (fase de
-- montagem do pedido, antes de confirmar) e "recebido" passa a
-- significar exclusivamente "confirmado, cozinha recebeu" dai em
-- diante. A ordem importa: o ALTER precisa adicionar 'montagem' ao
-- ENUM ANTES do UPDATE poder usa-lo.
ALTER TABLE pedidos
    MODIFY status ENUM('montagem', 'recebido', 'em_preparo', 'finalizado', 'saiu_para_entrega', 'entregue', 'cancelado')
    NOT NULL DEFAULT 'montagem';

-- Todo pedido que estava "recebido" sob a semantica antiga (Fase 5)
-- era, por definicao, um pedido nunca confirmado (finalizar() so roda
-- a partir de "recebido" e sai direto pra "em_preparo" antes desta
-- migration) - entao migra pra "montagem" com seguranca.
UPDATE pedidos SET status = 'montagem' WHERE status = 'recebido';

-- Caixa (abertura/fechamento de turno) - clone quase direto do model
-- ja usado em Barbearias\Models\Caixa.
CREATE TABLE IF NOT EXISTS caixas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NULL,
    status ENUM('aberto', 'fechado') NOT NULL DEFAULT 'aberto',
    valor_abertura DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    valor_fechamento_informado DECIMAL(10, 2) NULL,
    observacoes_abertura TEXT NULL,
    observacoes_fechamento TEXT NULL,
    aberto_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechado_em TIMESTAMP NULL,
    KEY caixas_restaurante_id_index (restaurante_id),
    CONSTRAINT caixas_restaurante_id_foreign
        FOREIGN KEY (restaurante_id) REFERENCES restaurantes (id) ON DELETE CASCADE,
    CONSTRAINT caixas_usuario_id_foreign
        FOREIGN KEY (usuario_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- financeiro_lancamentos ganha um vinculo opcional com o caixa aberto
-- no momento (pra sangria/suprimento e vendas do PDV aparecerem na
-- conferencia do caixa) - lancamentos automaticos de Pedido::finalizar()
-- fora do PDV continuam com caixa_id NULL.
ALTER TABLE financeiro_lancamentos
    ADD COLUMN caixa_id INT UNSIGNED NULL AFTER pedido_id,
    ADD CONSTRAINT financeiro_lancamentos_caixa_id_foreign
        FOREIGN KEY (caixa_id) REFERENCES caixas (id) ON DELETE SET NULL;

-- A Fase 5 criou financeiro_lancamentos.pedido_id como UNIQUE (um
-- lancamento por pedido). O PDV (Fase 6) precisa de split payment -
-- varias formas de pagamento numa mesma venda -> varios lancamentos
-- pro mesmo pedido_id. Relaxa pra indice normal (nao-unico).
ALTER TABLE financeiro_lancamentos
    DROP INDEX financeiro_lancamentos_pedido_id_unique,
    ADD KEY financeiro_lancamentos_pedido_id_index (pedido_id);

-- Detalhamento das formas de pagamento de um pedido (split payment do
-- PDV). Se um pedido nao tiver nenhuma linha aqui, Pedido::finalizar()
-- cai no comportamento antigo (Fase 5): um unico lancamento usando
-- pedidos.forma_pagamento pro valor_total inteiro - nao quebra pedidos
-- criados pela tela normal de Pedidos (sem PDV).
CREATE TABLE IF NOT EXISTS pedido_pagamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    forma_pagamento ENUM('dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'outro') NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    valor_recebido DECIMAL(10, 2) NULL,
    troco DECIMAL(10, 2) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY pedido_pagamentos_pedido_id_index (pedido_id),
    CONSTRAINT pedido_pagamentos_pedido_id_foreign
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
