<?php

declare(strict_types=1);

namespace Kadosys\Core\Database;

use Kadosys\Core\Support\Tenant;

/**
 * Model
 *
 * Classe base para todos os Models da plataforma (Core e aplicativos).
 * Implementa um padrao ActiveRecord simples sobre o QueryBuilder,
 * com suporte nativo a multi-tenancy: toda consulta e automaticamente
 * filtrada por tenant_id quando a coluna existir na tabela e o
 * isolamento estiver habilitado no Model (propriedade $tenantAware).
 *
 * Cada aplicativo (igrejas, crm, etc) deve estender esta classe
 * para seus proprios Models, nunca reimplementando persistencia.
 */
abstract class Model
{
    /**
     * Nome da tabela no banco de dados. Deve ser definido na subclasse.
     */
    protected static string $table;

    /**
     * Nome da chave primaria.
     */
    protected static string $primaryKey = 'id';

    /**
     * Define se este Model deve ser automaticamente filtrado por tenant_id.
     */
    protected static bool $tenantAware = true;

    /**
     * Atributos do registro atual.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Cria um QueryBuilder ja aplicando o filtro de tenant_id, se aplicavel.
     */
    protected static function newQuery(): QueryBuilder
    {
        $query = new QueryBuilder(static::$table);

        if (static::$tenantAware && Tenant::hasContext()) {
            $query->where('tenant_id', '=', Tenant::current());
        }

        return $query;
    }

    /**
     * Retorna todos os registros (respeitando o tenant atual).
     *
     * @return array<int, static>
     */
    public static function all(): array
    {
        $rows = static::newQuery()->get();

        return array_map(static fn (array $row) => new static($row), $rows);
    }

    /**
     * Busca um registro pela chave primaria.
     */
    public static function find(int|string $id): ?static
    {
        $row = static::newQuery()
            ->where(static::$primaryKey, '=', $id)
            ->first();

        return $row !== null ? new static($row) : null;
    }

    /**
     * Inicia uma query encadeavel a partir deste Model.
     */
    public static function where(string $column, string $operator, mixed $value): QueryBuilder
    {
        return static::newQuery()->where($column, $operator, $value);
    }

    /**
     * Cria e persiste um novo registro, injetando tenant_id automaticamente.
     *
     * @param array<string, mixed> $data
     */
    public static function create(array $data): static
    {
        if (static::$tenantAware && Tenant::hasContext() && !isset($data['tenant_id'])) {
            $data['tenant_id'] = Tenant::current();
        }

        $id = (new QueryBuilder(static::$table))->insert($data);
        $data[static::$primaryKey] = $id;

        return new static($data);
    }

    /**
     * Atualiza o registro atual com os novos dados informados.
     *
     * @param array<string, mixed> $data
     */
    public function update(array $data): bool
    {
        $id = $this->attributes[static::$primaryKey] ?? null;

        if ($id === null) {
            return false;
        }

        $affected = static::newQuery()
            ->where(static::$primaryKey, '=', $id)
            ->update($data);

        $this->attributes = array_merge($this->attributes, $data);

        return $affected > 0;
    }

    /**
     * Remove o registro atual.
     */
    public function delete(): bool
    {
        $id = $this->attributes[static::$primaryKey] ?? null;

        if ($id === null) {
            return false;
        }

        return static::newQuery()
            ->where(static::$primaryKey, '=', $id)
            ->delete() > 0;
    }

    public static function table(): string
    {
        return static::$table;
    }
}
