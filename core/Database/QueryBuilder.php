<?php

declare(strict_types=1);

namespace Kadosys\Core\Database;

/**
 * QueryBuilder
 *
 * Construtor de queries SQL fluente, com prepared statements,
 * usado pelo Model base e diretamente quando necessario.
 *
 * Exemplo:
 *   (new QueryBuilder('membros'))
 *       ->where('tenant_id', '=', 1)
 *       ->where('ativo', '=', true)
 *       ->orderBy('nome')
 *       ->limit(10)
 *       ->get();
 */
final class QueryBuilder
{
    private string $table;

    /** @var array<int, string> */
    private array $columns = ['*'];

    /** @var array<int, array{boolean: string, column: string, operator: string, value: mixed}> */
    private array $wheres = [];

    /** @var array<int, string> */
    private array $orders = [];

    private ?int $limit = null;

    private ?int $offset = null;

    private Connection $connection;

    public function __construct(string $table)
    {
        $this->table = $table;
        $this->connection = Connection::getInstance();
    }

    /**
     * @param array<int, string> $columns
     */
    public function select(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = [
            'boolean' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = [
            'boolean' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = "{$column} {$direction}";

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Executa a query SELECT e retorna todos os resultados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        [$sql, $bindings] = $this->toSelectSql();

        return $this->connection->statement($sql, $bindings)->fetchAll();
    }

    /**
     * Executa a query SELECT e retorna apenas o primeiro resultado.
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();

        return $results[0] ?? null;
    }

    public function count(): int
    {
        $original = $this->columns;
        $this->columns = ['COUNT(*) as total'];

        [$sql, $bindings] = $this->toSelectSql(ignoreLimit: true);
        $row = $this->connection->statement($sql, $bindings)->fetch();

        $this->columns = $original;

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Insere um registro e retorna o ultimo ID inserido.
     *
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $bindings = [];
        foreach ($data as $key => $value) {
            $bindings[':' . $key] = $value;
        }

        $this->connection->statement($sql, $bindings);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Atualiza registros que atendem as clausulas where definidas.
     *
     * @param array<string, mixed> $data
     */
    public function update(array $data): int
    {
        $setParts = [];
        $bindings = [];

        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :set_{$key}";
            $bindings[':set_' . $key] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s', $this->table, implode(', ', $setParts));

        [$whereSql, $whereBindings] = $this->compileWheres();
        $sql .= $whereSql;
        $bindings = array_merge($bindings, $whereBindings);

        return $this->connection->statement($sql, $bindings)->rowCount();
    }

    /**
     * Remove registros que atendem as clausulas where definidas.
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        [$whereSql, $bindings] = $this->compileWheres();
        $sql .= $whereSql;

        return $this->connection->statement($sql, $bindings)->rowCount();
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function toSelectSql(bool $ignoreLimit = false): array
    {
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $this->columns), $this->table);

        [$whereSql, $bindings] = $this->compileWheres();
        $sql .= $whereSql;

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if (!$ignoreLimit && $this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;

            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        return [$sql, $bindings];
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function compileWheres(): array
    {
        if (empty($this->wheres)) {
            return ['', []];
        }

        $clauses = [];
        $bindings = [];

        foreach ($this->wheres as $index => $where) {
            $placeholder = ':where_' . $index;
            $boolean = $index === 0 ? '' : " {$where['boolean']} ";
            $clauses[] = "{$boolean}{$where['column']} {$where['operator']} {$placeholder}";
            $bindings[$placeholder] = $where['value'];
        }

        return [' WHERE ' . implode('', $clauses), $bindings];
    }
}
