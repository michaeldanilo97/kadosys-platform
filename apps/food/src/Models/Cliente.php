<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Cliente da loja. Ticket medio/total gasto/frequencia/ultimo pedido
 * NAO ficam guardados aqui - sao calculados via query sobre `pedidos`
 * (ver estatisticas()), pra nunca ficarem desatualizados.
 */
final class Cliente
{
    private const SELECT_COLUNAS = 'id, restaurante_id, nome, telefone, whatsapp, aniversario, endereco,
        observacoes, ativo, created_at, updated_at';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly string $nome,
        public readonly ?string $telefone,
        public readonly ?string $whatsapp,
        public readonly ?string $aniversario,
        public readonly ?string $endereco,
        public readonly ?string $observacoes,
        public readonly bool $ativo,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $restauranteId, int $page, int $perPage, string $search = ''): array
    {
        $where = 'WHERE restaurante_id = :restaurante_id';
        $params = ['restaurante_id' => $restauranteId];

        if ($search !== '') {
            $where .= ' AND (nome LIKE :busca OR telefone LIKE :busca_telefone OR whatsapp LIKE :busca_whatsapp)';
            $params['busca'] = '%' . $search . '%';
            $params['busca_telefone'] = '%' . $search . '%';
            $params['busca_whatsapp'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM clientes {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
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
            'SELECT ' . self::SELECT_COLUNAS . ' FROM clientes WHERE id = :id AND restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function ativos(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM clientes WHERE restaurante_id = :restaurante_id AND ativo = 1 ORDER BY nome ASC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(
        int $restauranteId,
        string $nome,
        ?string $telefone,
        ?string $whatsapp,
        ?string $aniversario,
        ?string $endereco,
        ?string $observacoes,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO clientes (restaurante_id, nome, telefone, whatsapp, aniversario, endereco, observacoes, ativo, created_at, updated_at)
             VALUES (:restaurante_id, :nome, :telefone, :whatsapp, :aniversario, :endereco, :observacoes, 1, NOW(), NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'nome' => trim($nome),
            'telefone' => $telefone,
            'whatsapp' => $whatsapp,
            'aniversario' => $aniversario,
            'endereco' => $endereco,
            'observacoes' => $observacoes,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(
        int $id,
        int $restauranteId,
        string $nome,
        ?string $telefone,
        ?string $whatsapp,
        ?string $aniversario,
        ?string $endereco,
        ?string $observacoes,
        bool $ativo,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE clientes SET nome = :nome, telefone = :telefone, whatsapp = :whatsapp, aniversario = :aniversario,
                endereco = :endereco, observacoes = :observacoes, ativo = :ativo, updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'telefone' => $telefone,
            'whatsapp' => $whatsapp,
            'aniversario' => $aniversario,
            'endereco' => $endereco,
            'observacoes' => $observacoes,
            'ativo' => $ativo ? 1 : 0,
            'id' => $id,
            'restaurante_id' => $restauranteId,
        ]);
    }

    public static function delete(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM clientes WHERE id = :id AND restaurante_id = :restaurante_id');
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    /**
     * Metricas calculadas na hora sobre os pedidos do cliente - so
     * conta pedidos ja confirmados (status diferente de "recebido" -
     * ainda em montagem - e de "cancelado").
     *
     * @return array{totalPedidos: int, totalGasto: float, ticketMedio: float, ultimoPedidoEm: ?string}
     */
    public static function estatisticas(int $id, int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) AS total_pedidos, COALESCE(SUM(valor_total), 0) AS total_gasto,
                COALESCE(AVG(valor_total), 0) AS ticket_medio, MAX(created_at) AS ultimo_pedido_em
             FROM pedidos
             WHERE cliente_id = :id AND restaurante_id = :restaurante_id AND status NOT IN ('recebido', 'cancelado')"
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return [
            'totalPedidos' => (int) ($row['total_pedidos'] ?? 0),
            'totalGasto' => (float) ($row['total_gasto'] ?? 0),
            'ticketMedio' => (float) ($row['ticket_medio'] ?? 0),
            'ultimoPedidoEm' => $row['ultimo_pedido_em'] !== null ? (string) $row['ultimo_pedido_em'] : null,
        ];
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM clientes {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            nome: (string) $row['nome'],
            telefone: $row['telefone'] !== null ? (string) $row['telefone'] : null,
            whatsapp: $row['whatsapp'] !== null ? (string) $row['whatsapp'] : null,
            aniversario: $row['aniversario'] !== null ? (string) $row['aniversario'] : null,
            endereco: $row['endereco'] !== null ? (string) $row['endereco'] : null,
            observacoes: $row['observacoes'] !== null ? (string) $row['observacoes'] : null,
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
