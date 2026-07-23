<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Item de uma compra. "unidade" e sempre copiada do ingrediente no
 * momento da compra (mesma logica de FichaTecnicaItem - sem conversor
 * de unidades). Ao ser criado, atualiza o estoque/preco do ingrediente
 * de verdade e loga a movimentacao - ver create().
 */
final class CompraItem
{
    private const SELECT_COLUNAS = 'ci.id, ci.compra_id, ci.ingrediente_id, ci.quantidade, ci.unidade,
        ci.preco_unitario, ci.subtotal, ci.validade, ci.created_at, i.nome AS ingrediente_nome';

    private const JOINS = 'FROM compra_itens ci INNER JOIN ingredientes i ON i.id = ci.ingrediente_id';

    public function __construct(
        public readonly int $id,
        public readonly int $compraId,
        public readonly int $ingredienteId,
        public readonly float $quantidade,
        public readonly string $unidade,
        public readonly float $precoUnitario,
        public readonly float $subtotal,
        public readonly ?string $validade,
        public readonly string $ingredienteNome,
        public readonly ?string $createdAt = null,
    ) {
    }

    /** @return array<int, self> */
    public static function doCompra(int $compraId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . ' WHERE ci.compra_id = :compra_id ORDER BY ci.id ASC'
        );
        $stmt->execute(['compra_id' => $compraId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Itens de compra com validade dentro dos proximos N dias (inclui
     * ja vencidos) - alimenta o alerta de "vencendo em breve" na tela
     * de Estoque. Sem rastreamento de lote/FEFO completo: e so um
     * aviso, nao impede a venda nem exige consumir o lote mais antigo
     * primeiro.
     *
     * @return array<int, self>
     */
    public static function vencendoEm(int $restauranteId, int $dias): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . '
             INNER JOIN compras c ON c.id = ci.compra_id
             WHERE c.restaurante_id = :restaurante_id AND ci.validade IS NOT NULL
                AND ci.validade <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)
             ORDER BY ci.validade ASC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId, 'dias' => $dias]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Cria o item de compra e aplica os efeitos reais: soma o estoque
     * e atualiza preco_atual/preco_medio/ultima_compra do ingrediente
     * (ver Ingrediente::registrarEntradaCompra()), loga a movimentacao
     * e recalcula o valor_total da compra. Quem dispara o recalculo de
     * custo dos produtos afetados e o controller, logo em seguida
     * (mesmo padrao de IngredienteController::update()).
     */
    public static function create(
        int $compraId,
        int $restauranteId,
        int $ingredienteId,
        float $quantidade,
        string $unidade,
        float $precoUnitario,
        ?string $validade,
        string $dataCompra,
    ): int {
        $subtotal = round($quantidade * $precoUnitario, 2);

        $stmt = Database::connection()->prepare(
            'INSERT INTO compra_itens (compra_id, ingrediente_id, quantidade, unidade, preco_unitario, subtotal, validade, created_at)
             VALUES (:compra_id, :ingrediente_id, :quantidade, :unidade, :preco_unitario, :subtotal, :validade, NOW())'
        );
        $stmt->execute([
            'compra_id' => $compraId,
            'ingrediente_id' => $ingredienteId,
            'quantidade' => $quantidade,
            'unidade' => $unidade,
            'preco_unitario' => $precoUnitario,
            'subtotal' => $subtotal,
            'validade' => $validade,
        ]);

        $id = (int) Database::connection()->lastInsertId();

        Ingrediente::registrarEntradaCompra($ingredienteId, $restauranteId, $quantidade, $precoUnitario, $dataCompra);
        EstoqueMovimento::create($restauranteId, $ingredienteId, EstoqueMovimento::TIPO_ENTRADA, $quantidade, 'Compra #' . $compraId, 'compra', $compraId);
        Compra::recalcularValorTotal($compraId, $restauranteId);

        return $id;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            compraId: (int) $row['compra_id'],
            ingredienteId: (int) $row['ingrediente_id'],
            quantidade: (float) $row['quantidade'],
            unidade: (string) $row['unidade'],
            precoUnitario: (float) $row['preco_unitario'],
            subtotal: (float) $row['subtotal'],
            validade: $row['validade'] !== null ? (string) $row['validade'] : null,
            ingredienteNome: (string) $row['ingrediente_nome'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
