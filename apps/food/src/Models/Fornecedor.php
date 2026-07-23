<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Fornecedor de ingrediente. "Produtos fornecidos" e "ultima compra/
 * historico" nao ficam aqui - sao derivados da tabela `compras` (Fase
 * 4) via ingredientes.fornecedor_id, sem precisar de tabela extra.
 */
final class Fornecedor
{
    private const SELECT_COLUNAS = 'id, restaurante_id, nome, telefone, whatsapp, email, contato,
        prazo_dias, forma_pagamento, observacoes, created_at, updated_at';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly string $nome,
        public readonly ?string $telefone,
        public readonly ?string $whatsapp,
        public readonly ?string $email,
        public readonly ?string $contato,
        public readonly ?int $prazoDias,
        public readonly ?string $formaPagamento,
        public readonly ?string $observacoes,
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
            $where .= ' AND nome LIKE :busca';
            $params['busca'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM fornecedores {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
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
            'SELECT ' . self::SELECT_COLUNAS . ' FROM fornecedores WHERE id = :id AND restaurante_id = :restaurante_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function doRestaurante(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM fornecedores WHERE restaurante_id = :restaurante_id ORDER BY nome ASC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(
        int $restauranteId,
        string $nome,
        ?string $telefone,
        ?string $whatsapp,
        ?string $email,
        ?string $contato,
        ?int $prazoDias,
        ?string $formaPagamento,
        ?string $observacoes,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO fornecedores (restaurante_id, nome, telefone, whatsapp, email, contato,
                prazo_dias, forma_pagamento, observacoes, created_at, updated_at)
             VALUES (:restaurante_id, :nome, :telefone, :whatsapp, :email, :contato,
                :prazo_dias, :forma_pagamento, :observacoes, NOW(), NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'nome' => trim($nome),
            'telefone' => self::vazioParaNulo($telefone),
            'whatsapp' => self::vazioParaNulo($whatsapp),
            'email' => self::vazioParaNulo($email),
            'contato' => self::vazioParaNulo($contato),
            'prazo_dias' => $prazoDias,
            'forma_pagamento' => self::vazioParaNulo($formaPagamento),
            'observacoes' => self::vazioParaNulo($observacoes),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(
        int $id,
        int $restauranteId,
        string $nome,
        ?string $telefone,
        ?string $whatsapp,
        ?string $email,
        ?string $contato,
        ?int $prazoDias,
        ?string $formaPagamento,
        ?string $observacoes,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE fornecedores SET nome = :nome, telefone = :telefone, whatsapp = :whatsapp,
                email = :email, contato = :contato, prazo_dias = :prazo_dias,
                forma_pagamento = :forma_pagamento, observacoes = :observacoes, updated_at = NOW()
             WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'telefone' => self::vazioParaNulo($telefone),
            'whatsapp' => self::vazioParaNulo($whatsapp),
            'email' => self::vazioParaNulo($email),
            'contato' => self::vazioParaNulo($contato),
            'prazo_dias' => $prazoDias,
            'forma_pagamento' => self::vazioParaNulo($formaPagamento),
            'observacoes' => self::vazioParaNulo($observacoes),
            'id' => $id,
            'restaurante_id' => $restauranteId,
        ]);
    }

    public static function delete(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM fornecedores WHERE id = :id AND restaurante_id = :restaurante_id');
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    private static function vazioParaNulo(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim($valor);

        return $valor === '' ? null : $valor;
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM fornecedores {$where}");
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
            email: $row['email'] !== null ? (string) $row['email'] : null,
            contato: $row['contato'] !== null ? (string) $row['contato'] : null,
            prazoDias: $row['prazo_dias'] !== null ? (int) $row['prazo_dias'] : null,
            formaPagamento: $row['forma_pagamento'] !== null ? (string) $row['forma_pagamento'] : null,
            observacoes: $row['observacoes'] !== null ? (string) $row['observacoes'] : null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (string) $row['updated_at'] : null,
        );
    }
}
