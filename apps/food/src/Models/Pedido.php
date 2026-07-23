<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Pedido (balcao/WhatsApp/iFood manual/delivery proprio). O "status"
 * ja nasce com os 6 valores do fluxo de producao completo descrito no
 * spec original (Recebido/Em preparo/Finalizado/Saiu para entrega/
 * Entregue/Cancelado), mesmo a tela de producao (TV da cozinha) so
 * chegando na Fase 6.
 *
 * NESTA fase, o pedido e montado (itens adicionados um a um, sem
 * nenhum efeito real ainda - mesmo padrao de FichaTecnicaItem) enquanto
 * status = "montagem". `finalizar()` e o unico avanco de status
 * disponivel aqui: confirma o pedido de vez, baixando o estoque de
 * verdade (montagem -> recebido - a cozinha passa a ver o pedido a
 * partir daqui). Os demais avancos (recebido -> em_preparo ->
 * finalizado -> saiu_para_entrega -> entregue) sao expostos pela tela
 * de Producao da Fase 6, sem precisar tocar em estoque de novo.
 */
final class Pedido
{
    public const ORIGEM_BALCAO = 'balcao';
    public const ORIGEM_WHATSAPP = 'whatsapp';
    public const ORIGEM_IFOOD_MANUAL = 'ifood_manual';
    public const ORIGEM_DELIVERY_PROPRIO = 'delivery_proprio';

    /** @var array<int, string> */
    public const ORIGENS_VALIDAS = [self::ORIGEM_BALCAO, self::ORIGEM_WHATSAPP, self::ORIGEM_IFOOD_MANUAL, self::ORIGEM_DELIVERY_PROPRIO];

    public const STATUS_MONTAGEM = 'montagem';
    public const STATUS_RECEBIDO = 'recebido';
    public const STATUS_EM_PREPARO = 'em_preparo';
    public const STATUS_FINALIZADO = 'finalizado';
    public const STATUS_SAIU_PARA_ENTREGA = 'saiu_para_entrega';
    public const STATUS_ENTREGUE = 'entregue';
    public const STATUS_CANCELADO = 'cancelado';

    /**
     * Transicoes de status permitidas na tela de Producao (Fase 6) -
     * "montagem" nao entra aqui porque so sai dela via finalizar()
     * (que tambem baixa estoque), nunca por um avanco simples.
     *
     * @var array<string, string>
     */
    public const PROXIMO_STATUS_PRODUCAO = [
        self::STATUS_RECEBIDO => self::STATUS_EM_PREPARO,
        self::STATUS_EM_PREPARO => self::STATUS_FINALIZADO,
        self::STATUS_FINALIZADO => self::STATUS_SAIU_PARA_ENTREGA,
        self::STATUS_SAIU_PARA_ENTREGA => self::STATUS_ENTREGUE,
    ];

    /** @var array<int, string> */
    public const FORMAS_PAGAMENTO = ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'outro'];

    private const SELECT_COLUNAS = 'p.id, p.restaurante_id, p.cliente_id, p.origem, p.status, p.forma_pagamento,
        p.endereco_entrega, p.cupom, p.desconto, p.subtotal, p.valor_total, p.observacoes, p.created_at, p.updated_at,
        c.nome AS cliente_nome';

    private const JOINS = 'FROM pedidos p LEFT JOIN clientes c ON c.id = p.cliente_id';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly ?int $clienteId,
        public readonly string $origem,
        public readonly string $status,
        public readonly string $formaPagamento,
        public readonly ?string $enderecoEntrega,
        public readonly ?string $cupom,
        public readonly float $desconto,
        public readonly float $subtotal,
        public readonly float $valorTotal,
        public readonly ?string $observacoes,
        public readonly ?string $clienteNome,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $restauranteId, int $page, int $perPage, string $search = '', ?string $status = null): array
    {
        $where = 'WHERE p.restaurante_id = :restaurante_id';
        $params = ['restaurante_id' => $restauranteId];

        if ($search !== '') {
            $where .= ' AND c.nome LIKE :busca';
            $params['busca'] = '%' . $search . '%';
        }

        if ($status !== null) {
            $where .= ' AND p.status = :status';
            $params['status'] = $status;
        }

        $stmtTotal = Database::connection()->prepare("SELECT COUNT(*) FROM pedidos p LEFT JOIN clientes c ON c.id = p.cliente_id {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . " {$where} ORDER BY p.created_at DESC, p.id DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'items' => array_map(self::fromRow(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public static function find(int $id, int $restauranteId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . ' WHERE p.id = :id AND p.restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function create(
        int $restauranteId,
        ?int $clienteId,
        string $origem,
        string $formaPagamento,
        ?string $enderecoEntrega,
        ?string $cupom,
        float $desconto,
        ?string $observacoes,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO pedidos (restaurante_id, cliente_id, origem, status, forma_pagamento, endereco_entrega,
                cupom, desconto, subtotal, valor_total, observacoes, created_at, updated_at)
             VALUES (:restaurante_id, :cliente_id, :origem, :status, :forma_pagamento, :endereco_entrega,
                :cupom, :desconto, 0, 0, :observacoes, NOW(), NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'cliente_id' => $clienteId,
            'origem' => $origem,
            'status' => self::STATUS_MONTAGEM,
            'forma_pagamento' => $formaPagamento,
            'endereco_entrega' => $enderecoEntrega,
            'cupom' => $cupom,
            'desconto' => $desconto,
            'observacoes' => $observacoes,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Recalcula subtotal (soma dos itens) e valor_total (subtotal -
     * desconto, nunca negativo) - chamado sempre que um item e
     * adicionado/removido.
     */
    public static function recalcularValores(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE pedidos SET subtotal = (
                SELECT COALESCE(SUM(subtotal), 0) FROM pedido_itens WHERE pedido_id = pedidos.id
             ), valor_total = GREATEST(0, (
                SELECT COALESCE(SUM(subtotal), 0) FROM pedido_itens WHERE pedido_id = pedidos.id
             ) - desconto), updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    /**
     * Confirma o pedido: dentro de UMA transacao, percorre cada item ->
     * ficha tecnica do produto -> desconta o estoque de cada ingrediente
     * (mesmo UPDATE condicional atomico usado no resto da plataforma).
     * Se faltar estoque de QUALQUER ingrediente, a transacao inteira e
     * revertida e o pedido continua "montagem" (nada e alterado) - erro
     * indica exatamente qual ingrediente faltou. Em caso de sucesso,
     * cria o lancamento financeiro de receita e avanca o status pra
     * "recebido" (cozinha recebeu, visivel na tela de Producao).
     *
     * "$caixaId" e usado pelo PDV (Fase 6) pra vincular o(s) lancamento(s)
     * financeiro(s) ao caixa aberto no momento da venda - chamadas fora
     * do PDV (tela normal de Pedidos) nao passam nada e continuam com
     * caixa_id NULL, exatamente como na Fase 5.
     *
     * @return array{sucesso: bool, erro: ?string}
     */
    public static function finalizar(int $id, int $restauranteId, ?int $caixaId = null): array
    {
        $pedido = self::find($id, $restauranteId);

        if ($pedido === null) {
            return ['sucesso' => false, 'erro' => 'Pedido não encontrado.'];
        }

        if ($pedido->status !== self::STATUS_MONTAGEM) {
            return ['sucesso' => false, 'erro' => 'Esse pedido já foi confirmado ou cancelado.'];
        }

        $itens = PedidoItem::doPedido($id);

        if ($itens === []) {
            return ['sucesso' => false, 'erro' => 'Adicione ao menos um item antes de confirmar o pedido.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            foreach ($itens as $item) {
                $produto = Produto::find($item->produtoId, $restauranteId);

                if ($produto === null) {
                    $pdo->rollBack();

                    return ['sucesso' => false, 'erro' => 'Um dos produtos do pedido não foi encontrado.'];
                }

                $rendimento = max(1, $produto->rendimento);

                foreach (FichaTecnicaItem::doProduto($item->produtoId) as $fichaItem) {
                    $consumo = ($item->quantidade * $fichaItem->quantidade * (1 + $fichaItem->perdaPercentual / 100)) / $rendimento;

                    $stmtBaixa = $pdo->prepare(
                        'UPDATE ingredientes SET estoque_atual = estoque_atual - :consumo, updated_at = NOW()
                         WHERE id = :id AND restaurante_id = :restaurante_id AND estoque_atual >= :consumo_check'
                    );
                    $stmtBaixa->execute([
                        'consumo' => $consumo,
                        'consumo_check' => $consumo,
                        'id' => $fichaItem->ingredienteId,
                        'restaurante_id' => $restauranteId,
                    ]);

                    if ($stmtBaixa->rowCount() === 0) {
                        $pdo->rollBack();

                        return [
                            'sucesso' => false,
                            'erro' => 'Estoque insuficiente de "' . $fichaItem->ingredienteNome . '" para confirmar o pedido.',
                        ];
                    }

                    EstoqueMovimento::create(
                        $restauranteId,
                        $fichaItem->ingredienteId,
                        EstoqueMovimento::TIPO_SAIDA,
                        $consumo,
                        'Pedido #' . $id,
                        'pedido',
                        $id,
                    );
                }
            }

            $stmtStatus = $pdo->prepare(
                'UPDATE pedidos SET status = :status, updated_at = NOW() WHERE id = :id AND restaurante_id = :restaurante_id'
            );
            $stmtStatus->execute(['status' => self::STATUS_RECEBIDO, 'id' => $id, 'restaurante_id' => $restauranteId]);

            $pagamentos = PedidoPagamento::doPedido($id);
            $dataLancamento = (new \DateTimeImmutable())->format('Y-m-d');

            if ($pagamentos !== []) {
                // Split payment (PDV): um lancamento por forma de pagamento.
                foreach ($pagamentos as $pagamento) {
                    FinanceiroLancamento::create(
                        $restauranteId,
                        $id,
                        FinanceiroLancamento::TIPO_RECEITA,
                        'Vendas',
                        $pagamento->formaPagamento,
                        $pagamento->valor,
                        'Pedido #' . $id,
                        $dataLancamento,
                        $caixaId,
                    );
                }
            } else {
                // Comportamento da Fase 5: uma unica forma de pagamento pro valor_total inteiro.
                FinanceiroLancamento::create(
                    $restauranteId,
                    $id,
                    FinanceiroLancamento::TIPO_RECEITA,
                    'Vendas',
                    $pedido->formaPagamento,
                    $pedido->valorTotal,
                    'Pedido #' . $id,
                    $dataLancamento,
                    $caixaId,
                );
            }

            $pdo->commit();

            return ['sucesso' => true, 'erro' => null];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Cancela o pedido - so permitido enquanto ainda esta "montagem"
     * (nenhum estoque foi baixado ainda). Cancelar um pedido ja
     * confirmado exigiria reverter a baixa de estoque com seguranca,
     * fora de escopo aqui (mesma logica conservadora ja aplicada as
     * compras).
     */
    public static function cancelar(int $id, int $restauranteId): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE pedidos SET status = :status, updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id AND status = 'montagem'"
        );
        $stmt->execute(['status' => self::STATUS_CANCELADO, 'id' => $id, 'restaurante_id' => $restauranteId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Avanca o pedido pra proxima etapa da Producao (recebido ->
     * em_preparo -> finalizado -> saiu_para_entrega -> entregue) - sem
     * nenhum efeito colateral em estoque/financeiro, que ja aconteceram
     * em finalizar(). So aceita o status atual exato como condicao no
     * WHERE (idempotente/seguro contra clique duplo ou duas abas).
     */
    public static function avancarStatus(int $id, int $restauranteId): bool
    {
        $pedido = self::find($id, $restauranteId);

        if ($pedido === null || !isset(self::PROXIMO_STATUS_PRODUCAO[$pedido->status])) {
            return false;
        }

        $proximo = self::PROXIMO_STATUS_PRODUCAO[$pedido->status];

        $stmt = Database::connection()->prepare(
            'UPDATE pedidos SET status = :novo_status, updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id AND status = :status_atual'
        );
        $stmt->execute([
            'novo_status' => $proximo,
            'id' => $id,
            'restaurante_id' => $restauranteId,
            'status_atual' => $pedido->status,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Pedidos "em producao" (ja confirmados, ainda nao entregues nem
     * cancelados) pra tela de cozinha/TV - ordenados do mais antigo pro
     * mais novo (FIFO), que e como uma cozinha real prioriza.
     *
     * @return array<int, self>
     */
    public static function emProducao(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . '
             WHERE p.restaurante_id = :restaurante_id
               AND p.status IN (:recebido, :em_preparo, :finalizado, :saiu_para_entrega)
             ORDER BY p.created_at ASC, p.id ASC'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'recebido' => self::STATUS_RECEBIDO,
            'em_preparo' => self::STATUS_EM_PREPARO,
            'finalizado' => self::STATUS_FINALIZADO,
            'saiu_para_entrega' => self::STATUS_SAIU_PARA_ENTREGA,
        ]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Pedidos entregues HOJE - ultima coluna do kanban de Producao, so
     * pra dar visibilidade do que acabou de sair sem o quadro crescer
     * pra sempre (pedidos entregues em dias anteriores nao aparecem
     * mais aqui, so no historico normal de Pedidos).
     *
     * @return array<int, self>
     */
    public static function entreguesHoje(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . '
             WHERE p.restaurante_id = :restaurante_id
               AND p.status = :entregue
               AND DATE(p.updated_at) = CURDATE()
             ORDER BY p.updated_at DESC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId, 'entregue' => self::STATUS_ENTREGUE]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            clienteId: $row['cliente_id'] !== null ? (int) $row['cliente_id'] : null,
            origem: (string) $row['origem'],
            status: (string) $row['status'],
            formaPagamento: (string) $row['forma_pagamento'],
            enderecoEntrega: $row['endereco_entrega'] !== null ? (string) $row['endereco_entrega'] : null,
            cupom: $row['cupom'] !== null ? (string) $row['cupom'] : null,
            desconto: (float) $row['desconto'],
            subtotal: (float) $row['subtotal'],
            valorTotal: (float) $row['valor_total'],
            observacoes: $row['observacoes'] !== null ? (string) $row['observacoes'] : null,
            clienteNome: $row['cliente_nome'] !== null ? (string) $row['cliente_nome'] : null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
