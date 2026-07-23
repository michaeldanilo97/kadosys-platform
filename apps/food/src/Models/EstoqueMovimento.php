<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Log auditavel de movimentacao de estoque. `ingrediente.estoque_atual`
 * continua sendo o cache rapido consultado no dia a dia - esta tabela
 * so existe pro historico/auditoria de "o que aconteceu e quando".
 *
 * "quantidade" e sempre um numero positivo (a direcao vem do "tipo")
 * EXCETO em ajustes de inventario, onde representa a contagem NOVA
 * total apos o ajuste (nao um delta) - contagem fisica substitui o
 * numero anterior.
 */
final class EstoqueMovimento
{
    public const TIPO_ENTRADA = 'entrada';
    public const TIPO_SAIDA = 'saida';
    public const TIPO_INVENTARIO = 'inventario';
    public const TIPO_PERDA = 'perda';

    /** @var array<int, string> */
    public const TIPOS_VALIDOS = [self::TIPO_ENTRADA, self::TIPO_SAIDA, self::TIPO_INVENTARIO, self::TIPO_PERDA];

    private const SELECT_COLUNAS = 'em.id, em.restaurante_id, em.ingrediente_id, em.tipo, em.quantidade,
        em.motivo, em.referencia_tipo, em.referencia_id, em.created_at, i.nome AS ingrediente_nome, i.unidade AS ingrediente_unidade';

    private const JOINS = 'FROM estoque_movimentos em INNER JOIN ingredientes i ON i.id = em.ingrediente_id';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly int $ingredienteId,
        public readonly string $tipo,
        public readonly float $quantidade,
        public readonly ?string $motivo,
        public readonly ?string $referenciaTipo,
        public readonly ?int $referenciaId,
        public readonly string $ingredienteNome,
        public readonly string $ingredienteUnidade,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $restauranteId, int $page, int $perPage, ?int $ingredienteId = null): array
    {
        $where = 'WHERE em.restaurante_id = :restaurante_id';
        $params = ['restaurante_id' => $restauranteId];

        if ($ingredienteId !== null) {
            $where .= ' AND em.ingrediente_id = :ingrediente_id';
            $params['ingrediente_id'] = $ingredienteId;
        }

        $stmtTotal = Database::connection()->prepare("SELECT COUNT(*) FROM estoque_movimentos em {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . " {$where} ORDER BY em.created_at DESC, em.id DESC LIMIT {$perPage} OFFSET {$offset}"
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

    /**
     * Registra uma movimentacao manual (nao vinda de uma Compra) e
     * atualiza o estoque_atual do ingrediente. Entrada soma, saida/perda
     * so aplicam se sobrar estoque suficiente (mesmo UPDATE condicional
     * atomico usado nos outros apps pra evitar estoque negativo), e
     * inventario define o novo total exato (a contagem fisica venceu).
     *
     * @return bool false se saida/perda foi recusada por falta de estoque
     */
    public static function registrarManual(
        int $restauranteId,
        int $ingredienteId,
        string $tipo,
        float $quantidade,
        ?string $motivo,
    ): bool {
        $pdo = Database::connection();

        if ($tipo === self::TIPO_ENTRADA) {
            $stmt = $pdo->prepare(
                'UPDATE ingredientes SET estoque_atual = estoque_atual + :quantidade, updated_at = NOW()
                 WHERE id = :id AND restaurante_id = :restaurante_id'
            );
            $stmt->execute(['quantidade' => $quantidade, 'id' => $ingredienteId, 'restaurante_id' => $restauranteId]);
        } elseif ($tipo === self::TIPO_SAIDA || $tipo === self::TIPO_PERDA) {
            $stmt = $pdo->prepare(
                'UPDATE ingredientes SET estoque_atual = estoque_atual - :quantidade, updated_at = NOW()
                 WHERE id = :id AND restaurante_id = :restaurante_id AND estoque_atual >= :quantidade_check'
            );
            $stmt->execute([
                'quantidade' => $quantidade,
                'quantidade_check' => $quantidade,
                'id' => $ingredienteId,
                'restaurante_id' => $restauranteId,
            ]);

            if ($stmt->rowCount() === 0) {
                return false;
            }
        } elseif ($tipo === self::TIPO_INVENTARIO) {
            $stmt = $pdo->prepare(
                'UPDATE ingredientes SET estoque_atual = :quantidade, updated_at = NOW()
                 WHERE id = :id AND restaurante_id = :restaurante_id'
            );
            $stmt->execute(['quantidade' => $quantidade, 'id' => $ingredienteId, 'restaurante_id' => $restauranteId]);
        } else {
            return false;
        }

        self::create($restauranteId, $ingredienteId, $tipo, $quantidade, $motivo, null, null);

        return true;
    }

    public static function create(
        int $restauranteId,
        int $ingredienteId,
        string $tipo,
        float $quantidade,
        ?string $motivo,
        ?string $referenciaTipo,
        ?int $referenciaId,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO estoque_movimentos (restaurante_id, ingrediente_id, tipo, quantidade, motivo,
                referencia_tipo, referencia_id, created_at)
             VALUES (:restaurante_id, :ingrediente_id, :tipo, :quantidade, :motivo, :referencia_tipo, :referencia_id, NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'ingrediente_id' => $ingredienteId,
            'tipo' => $tipo,
            'quantidade' => $quantidade,
            'motivo' => $motivo,
            'referencia_tipo' => $referenciaTipo,
            'referencia_id' => $referenciaId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            ingredienteId: (int) $row['ingrediente_id'],
            tipo: (string) $row['tipo'],
            quantidade: (float) $row['quantidade'],
            motivo: $row['motivo'] !== null ? (string) $row['motivo'] : null,
            referenciaTipo: $row['referencia_tipo'] !== null ? (string) $row['referencia_tipo'] : null,
            referenciaId: $row['referencia_id'] !== null ? (int) $row['referencia_id'] : null,
            ingredienteNome: (string) $row['ingrediente_nome'],
            ingredienteUnidade: (string) $row['ingrediente_unidade'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
