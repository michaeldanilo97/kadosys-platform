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
 * status = "recebido". `finalizar()` e o unico avanco de status
 * disponivel aqui: confirma o pedido de vez, baixando o estoque de
 * verdade (recebido -> em_preparo). Os demais avancos (em_preparo ->
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

    public const STATUS_RECEBIDO = 'recebido';
    public const STATUS_EM_PREPARO = 'em_preparo';
    public const STATUS_FINALIZADO = 'finalizado';
    public const STATUS_SAIU_PARA_ENTREGA = 'saiu_para_entrega';
    public const STATUS_ENTREGUE = 'entregue';
    public const STATUS_CANCELADO = 'cancelado';

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
            'status' => self::STATUS_RECEBIDO,
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
     * revertida e o pedido continua "recebido" (nada e alterado) - erro
     * indica exatamente qual ingrediente faltou. Em caso de sucesso,
     * cria o lancamento financeiro de receita e avanca o status pra
     * "em_preparo".
     *
     * @return array{sucesso: bool, erro: ?string}
     */
    public static function finalizar(int $id, int $restauranteId): array
    {
        $pedido = self::find($id, $restauranteId);

        if ($pedido === null) {
            return ['sucesso' => false, 'erro' => 'Pedido não encontrado.'];
        }

        if ($pedido->status !== self::STATUS_RECEBIDO) {
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
            $stmtStatus->execute(['status' => self::STATUS_EM_PREPARO, 'id' => $id, 'restaurante_id' => $restauranteId]);

            FinanceiroLancamento::create(
                $restauranteId,
                $id,
                FinanceiroLancamento::TIPO_RECEITA,
                'Vendas',
                $pedido->formaPagamento,
                $pedido->valorTotal,
                'Pedido #' . $id,
                (new \DateTimeImmutable())->format('Y-m-d'),
            );

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
     * Cancela o pedido - so permitido enquanto ainda esta "recebido"
     * (nenhum estoque foi baixado ainda). Cancelar um pedido ja
     * confirmado exigiria reverter a baixa de estoque com seguranca,
     * fora de escopo aqui (mesma logica conservadora ja aplicada as
     * compras).
     */
    public static function cancelar(int $id, int $restauranteId): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE pedidos SET status = :status, updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id AND status = 'recebido'"
        );
        $stmt->execute(['status' => self::STATUS_CANCELADO, 'id' => $id, 'restaurante_id' => $restauranteId]);

        return $stmt->rowCount() > 0;
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
